<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecuredInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\SecuredInstallmentOptionsStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SecuredInstallment\InstallmentOptionsRequestParameterBuilder
 */
class InstallmentOptionsRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItSupportsValidInstallmentOptionsRequest(): void
    {
        $struct = $this->getSecuredInstallmentOptionsStruct();
        $builder = $this->getContainer()->get(InstallmentOptionsRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsInvalidRequestAction(): void
    {
        $struct = $this->getSecuredInstallmentOptionsStruct(
            PayoneSecuredInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
        );

        $builder = $this->getContainer()->get(InstallmentOptionsRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsInvalidPaymentMethod(): void
    {
        $struct = $this->getSecuredInstallmentOptionsStruct(PayoneSecuredInvoicePaymentHandler::class);

        $builder = $this->getContainer()->get(InstallmentOptionsRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneSecuredInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(InstallmentOptionsRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsTheInstallmentOptionsRequestParameters(): void
    {
        $struct = $this->getSecuredInstallmentOptionsStruct();
        $builder = $this->getContainer()->get(InstallmentOptionsRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => 'genericpayment',
                'clearingtype' => InstallmentOptionsRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIN,
                'add_paydata[action]' => 'installment_options',
                'add_paydata[businessRelation]' => 'b2c',
                'amount' => 30000,
                'currency' => 'EUR',
            ],
            $parameters
        );
    }

    protected function getSecuredInstallmentOptionsStruct(
        string $paymentHandler = PayoneSecuredInstallmentPaymentHandler::class,
        string $requestAction = AbstractRequestParameterBuilder::REQUEST_ACTION_SECURED_INSTALLMENT_OPTIONS
    ): SecuredInstallmentOptionsStruct {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $cart = $this->fillCart($salesChannelContext->getToken(), 300);

        $dataBag = new RequestDataBag([]);

        return new SecuredInstallmentOptionsStruct(
            $cart,
            $dataBag,
            $salesChannelContext,
            $paymentHandler,
            $requestAction
        );
    }
}
