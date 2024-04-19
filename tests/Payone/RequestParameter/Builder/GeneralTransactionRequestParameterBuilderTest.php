<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\ClassHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder
 */
class GeneralTransactionRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    /**
     * @dataProvider allPaymentHandler
     * @testdox It supports payment handler $paymentHandler
     */
    public function testItSupportsAllPaymentHandler(string $paymentHandler): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            $paymentHandler,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = $this->getContainer()->get(GeneralTransactionRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequests(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new ParameterBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(GeneralTransactionRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectAmountAndCurrency(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');
        $struct->getPaymentTransaction()->getOrder()->setCurrency($currency);
        $struct->getPaymentTransaction()->getOrder()->setAmountTotal(100.1);

        /** @var MockObject&CurrencyPrecisionInterface $currencyPrecision */
        $currencyPrecision = $this->createMock(CurrencyPrecisionInterface::class);
        $currencyPrecision
            ->expects(static::once())
            ->method('getRoundedTotalAmount')
            ->with(
                100.1,
                $currency
            )
            ->willReturn(10010);

        $builder = new GeneralTransactionRequestParameterBuilder(
            new RequestBuilderServiceAccessor(
                $this->getContainer()->get('customer.repository'),
                $this->getContainer()->get('order_address.repository'),
                $this->getContainer()->get('customer_address.repository'),
                $this->getContainer()->get('currency.repository'),
                $currencyPrecision,
                $this->getContainer()->get(LineItemHydratorInterface::class)
            ),
            $this->createMock(CartHasherInterface::class),
            $this->createMock(ConfigReaderInterface::class)
        );

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame(10010, $parameters['amount']);
        static::assertSame('EUR', $parameters['currency']);
    }

    public function testItAddsCorrectWorkorderId(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::CART_HASH => 'the-hash',
                RequestConstants::WORK_ORDER_ID => 'the-workorder',
            ]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $cartHasher = $this->createMock(CartHasherInterface::class);
        $cartHasher->expects(static::once())->method('validate')->willReturn(true);

        $builder = new GeneralTransactionRequestParameterBuilder(
            $this->getContainer()->get(RequestBuilderServiceAccessor::class),
            $cartHasher,
            $this->createMock(ConfigReaderInterface::class)
        );

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('the-workorder', $parameters['workorderid']);
    }

    public function testItAddsNoWorkorderIdOnMissingHash(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::WORK_ORDER_ID => 'the-workorder',
            ]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = $this->getContainer()->get(GeneralTransactionRequestParameterBuilder::class);

        $parameters = $builder->getRequestParameter($struct);

        static::assertNull($parameters['workorderid']);
    }

    public function testItAddsNoWorkorderIdOnInvalidHash(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::CART_HASH => 'the-hash',
                RequestConstants::WORK_ORDER_ID => 'the-workorder',
            ]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $cartHasher = $this->createMock(CartHasherInterface::class);
        $cartHasher->expects(static::once())->method('validate')->willReturn(false);

        $builder = new GeneralTransactionRequestParameterBuilder(
            $this->getContainer()->get(RequestBuilderServiceAccessor::class),
            $cartHasher,
            $this->createMock(ConfigReaderInterface::class)
        );

        $parameters = $builder->getRequestParameter($struct);

        static::assertNull($parameters['workorderid']);
    }

    public function testItAddsOrderNumberAsReference(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $struct->getPaymentTransaction()->getOrder()->setOrderNumber('the-order-number');

        $builder = $this->getContainer()->get(GeneralTransactionRequestParameterBuilder::class);

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('the-order-number', $parameters['reference']);
    }

    public function testItAddsOrderNumberWithSuffixAsReference(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $fakeTransaction1 = $this->createMock(OrderTransactionEntity::class);
        $fakeTransaction1->method('getUniqueIdentifier')->willReturn('1');

        $fakeTransaction2 = $this->createMock(OrderTransactionEntity::class);
        $fakeTransaction2->method('getUniqueIdentifier')->willReturn('2');

        $struct->getPaymentTransaction()->getOrder()->setOrderNumber('the-order-number');
        $struct->getPaymentTransaction()->getOrder()->setTransactions(
            new OrderTransactionCollection(
                [
                    $fakeTransaction1,
                    $fakeTransaction2,
                ]
            )
        );

        $builder = $this->getContainer()->get(GeneralTransactionRequestParameterBuilder::class);

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('the-order-number.2', $parameters['reference']);
    }

    public function allPaymentHandler(): array
    {
        $data = [];
        foreach (ClassHelper::getPaymentHandlerClasses() as $paymentHandlerClass) {
            $data[] = [$paymentHandlerClass];
        }

        return $data;
    }
}
