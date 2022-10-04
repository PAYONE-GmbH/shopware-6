<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing\PreCheckRequestParameterBuilder
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
            PayonePayolutionInvoicingPaymentHandler::class,
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
            PayonePayolutionInvoicingPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectPreCheckParameters(): void
    {
        $struct     = $this->getPayolutionAdditionalActionStruct();
        $builder    = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                   => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype'              => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'             => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'add_paydata[action]'       => 'pre_check',
                'add_paydata[payment_type]' => 'Payolution-Invoicing',
                'amount'                    => 10000,
                'currency'                  => 'EUR',
                'workorderid'               => '',
                'birthday'                  => '20000101',
            ],
            $parameters
        );
    }

    public function testItAddsCorrectCompanyParametersByBillingAddress(): void
    {
        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $struct = $this->getPayolutionAdditionalActionStruct();
        $struct->getSalesChannelContext()->getCustomer()->getActiveBillingAddress()->setCompany('the-company');

        $builder    = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                   => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype'              => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'             => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'add_paydata[action]'       => 'pre_check',
                'add_paydata[payment_type]' => 'Payolution-Invoicing',
                'amount'                    => 10000,
                'currency'                  => 'EUR',
                'workorderid'               => '',
                'birthday'                  => '20000101',
                'add_paydata[b2b]'          => 'yes',
            ],
            $parameters
        );
    }

    public function testItAddsCorrectCompanyParametersByCustomer(): void
    {
        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $struct = $this->getPayolutionAdditionalActionStruct();
        $struct->getSalesChannelContext()->getCustomer()->setCompany('the-company');
        $struct->getSalesChannelContext()->getCustomer()->setVatIds(['the-vatid']);

        $builder    = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                   => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype'              => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'             => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'add_paydata[action]'       => 'pre_check',
                'add_paydata[payment_type]' => 'Payolution-Invoicing',
                'amount'                    => 10000,
                'currency'                  => 'EUR',
                'workorderid'               => '',
                'birthday'                  => '20000101',
                'add_paydata[b2b]'          => 'yes',
                'add_paydata[company_uid]'  => 'the-vatid',
            ],
            $parameters
        );
    }

    public function testItNotAddsCompanyParametersOnDeactivatedConfiguration(): void
    {
        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            false
        );

        $struct = $this->getPayolutionAdditionalActionStruct();
        $struct->getSalesChannelContext()->getCustomer()->setCompany('the-company');
        $struct->getSalesChannelContext()->getCustomer()->setVatIds(['the-vatid']);

        $builder    = $this->getContainer()->get(PreCheckRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertArrayNotHasKey('add_paydata[b2b]', $parameters);
        static::assertArrayNotHasKey('add_paydata[company_uid]', $parameters);
    }

    protected function getPayolutionAdditionalActionStruct(
        string $paymentHandler = PayonePayolutionInvoicingPaymentHandler::class,
        string $requestAction = AbstractRequestParameterBuilder::REQUEST_ACTION_PAYOLUTION_PRE_CHECK
    ): PayolutionAdditionalActionStruct {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $cart                = $this->fillCart($salesChannelContext->getToken(), 100);

        $dataBag = new RequestDataBag([
            'payolutionBirthday' => '2000-01-01',
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
