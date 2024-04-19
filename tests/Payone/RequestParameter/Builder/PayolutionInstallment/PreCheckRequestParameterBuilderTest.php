<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Constants;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment\PreCheckRequestParameterBuilder
 */
class PreCheckRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItSupportsValidPreCheckRequest(): void
    {
        $struct = $this->getPayolutionAdditionalActionStruct();

        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsInvalidRequestAction(): void
    {
        $struct = $this->getPayolutionAdditionalActionStruct(
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
        );

        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsInvalidPaymentMethod(): void
    {
        $struct = $this->getPayolutionAdditionalActionStruct(
            PayoneRatepayDebitPaymentHandler::class
        );

        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectPreCheckParameters(): void
    {
        $struct = $this->getPayolutionAdditionalActionStruct();
        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYS,
                'add_paydata[action]' => 'pre_check',
                'add_paydata[payment_type]' => 'Payolution-Installment',
                'amount' => Constants::DEFAULT_PRODUCT_PRICE * 100,
                'currency' => 'EUR',
                'workorderid' => '',
                'birthday' => '20000101',
            ],
            $parameters
        );
    }

    protected function getPayolutionAdditionalActionStruct(
        string $paymentHandler = PayonePayolutionInstallmentPaymentHandler::class,
        string $requestAction = AbstractRequestParameterBuilder::REQUEST_ACTION_PAYOLUTION_PRE_CHECK
    ): PayolutionAdditionalActionStruct {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $cart = $this->fillCart($salesChannelContext);

        $dataBag = new RequestDataBag([
            RequestConstants::BIRTHDAY => '2000-01-01',
        ]);

        return new PayolutionAdditionalActionStruct(
            $cart,
            $dataBag,
            $salesChannelContext,
            $paymentHandler,
            $requestAction
        );
    }
}
