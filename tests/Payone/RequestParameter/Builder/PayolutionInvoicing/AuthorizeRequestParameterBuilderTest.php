<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;
    use ConfigurationHelper;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'payolutionBirthday' => '2000-01-01',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'       => $this->getValidRequestAction(),
                'clearingtype'  => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'birthday'      => '20000101',
            ],
            $parameters
        );
    }

    public function testItAddsCorrectAuthorizeParametersForCompanyByBillingAddress(): void
    {
        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $dataBag = new RequestDataBag([
            'payolutionBirthday' => '2000-01-01',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $addressRepository = $this->getContainer()->get('order_address.repository');
        $order             = $struct->getPaymentTransaction()->getOrder();
        $addressRepository->update([
            [
                'id'      => $order->getBillingAddressId(),
                'company' => 'the-company',
                'vatId'   => 'the-vatid',
            ],
        ], Context::createDefaultContext());

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                  => $this->getValidRequestAction(),
                'clearingtype'             => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'            => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'birthday'                 => '20000101',
                'add_paydata[b2b]'         => 'yes',
                'add_paydata[company_uid]' => 'the-vatid',
            ],
            $parameters
        );
    }

    public function testItAddsCorrectAuthorizeParametersForCompanyByOrderCustomer(): void
    {
        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $dataBag = new RequestDataBag([
            'payolutionBirthday' => '2000-01-01',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $orderCustomerRepository = $this->getContainer()->get('order_customer.repository');
        $order                   = $struct->getPaymentTransaction()->getOrder();
        $orderCustomerRepository->update([
            [
                'id'      => $order->getOrderCustomer()->getId(),
                'company' => 'the-company',
                'vatIds'  => ['the-vatid'],
            ],
        ], Context::createDefaultContext());

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                  => $this->getValidRequestAction(),
                'clearingtype'             => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'            => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
                'birthday'                 => '20000101',
                'add_paydata[b2b]'         => 'yes',
                'add_paydata[company_uid]' => 'the-vatid',
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

        $dataBag = new RequestDataBag([
            'payolutionBirthday' => '2000-01-01',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $addressRepository = $this->getContainer()->get('order_address.repository');
        $order             = $struct->getPaymentTransaction()->getOrder();
        $addressRepository->update([
            [
                'id'      => $order->getBillingAddressId(),
                'company' => 'the-company',
                'vatId'   => 'the-vatid',
            ],
        ], Context::createDefaultContext());

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        static::assertArrayNotHasKey('add_paydata[b2b]', $parameters);
        static::assertArrayNotHasKey('add_paydata[company_uid]', $parameters);
    }

    protected function getParameterBuilder(): string
    {
        return AuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayonePayolutionInvoicingPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
