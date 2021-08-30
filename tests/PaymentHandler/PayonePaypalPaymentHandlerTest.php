<?php

declare(strict_types=1);

namespace PayonePayment\Test\PaymentHandler;

use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Translator;

class PayonePaypalPaymentHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    /** @var Translator */
    private $translator;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Translator $translator */
        $translator       = $this->getContainer()->get('translator');
        $this->translator = $translator;
    }

    public function testRequestOnPay(): void
    {
        $configReader = new ConfigReaderMock([
            'paypalAuthorizationMethod' => 'authorization',
        ]);

        $client         = $this->createMock(PayoneClientInterface::class);
        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $dataBag        = new RequestDataBag();

        $paymentHandler = new PayonePaypalPaymentHandler(
            $configReader,
            $client,
            $this->translator,
            new TransactionDataHandler($this->createMock(EntityRepositoryInterface::class), new CurrencyPrecision()),
            $this->createMock(EntityRepositoryInterface::class),
            new PaymentStateHandler($this->translator),
            $this->getRequestStack($dataBag),
            $requestFactory
        );

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentTransaction = $this->getPaymentTransaction();
        $dataBag            = new RequestDataBag();

        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => 'test-status',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $response = $paymentHandler->pay(
            new AsyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder(),
                ''
            ),
            $dataBag,
            Generator::createSalesChannelContext()
        );

        $this->assertEquals($response->getTargetUrl(), 'test-url');
    }

    protected function getPaymentTransaction(): PaymentTransaction
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayonePaypalPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    private function getRequestStack(RequestDataBag $dataBag): RequestStack
    {
        $stack = new RequestStack();

        $request = new Request([], $dataBag->all());
        $stack->push($request);

        return $stack;
    }
}
