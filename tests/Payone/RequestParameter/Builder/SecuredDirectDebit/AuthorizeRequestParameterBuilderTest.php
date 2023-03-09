<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecuredDirectDebit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DeviceFingerprint\PayoneBNPLDeviceFingerprintService;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SecuredDirectDebit\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $this->getContainer()->get(SessionInterface::class)->set(
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME,
            'the-device-ident-token'
        );

        $dataBag = new RequestDataBag([
            'securedDirectDebitPhone' => '0123456789',
            'securedDirectDebitBirthday' => '2000-01-01',
            'securedDirectDebitIban' => 'DE85500105173716329595',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => $this->getValidRequestAction(),
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PDD,
                'add_paydata[device_token]' => 'the-device-ident-token',
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'bankaccountholder' => 'Max Mustermann',
                'iban' => 'DE85500105173716329595',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );

        static::assertArrayHasKey('amount', $parameters);
        static::assertArrayHasKey('currency', $parameters);
    }

    public function testItThrowsExceptionOnMissingPhoneNumber(): void
    {
        $dataBag = new RequestDataBag([
            'securedDirectDebitBirthday' => '2000-01-01',
            'securedDirectDebitIban' => 'DE85500105173716329595',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing phone number');

        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnMissingBirthday(): void
    {
        $dataBag = new RequestDataBag([
            'securedDirectDebitPhone' => '0123456789',
            'securedDirectDebitIban' => 'DE85500105173716329595',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing birthday');

        $builder->getRequestParameter($struct);
    }

    public function testItAddsCorrectAuthorizeParametersWithSavedPhoneNumber(): void
    {
        $dataBag = new RequestDataBag([
            'securedDirectDebitBirthday' => '2000-01-01',
            'securedDirectDebitIban' => 'DE85500105173716329595',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        // Save phone number on customer custom fields
        $this->getContainer()->get('customer.repository')->update([
            [
                'id' => $struct->getPaymentTransaction()->getOrder()->getOrderCustomer()->getCustomerId(),
                'customFields' => [
                    CustomFieldInstaller::CUSTOMER_PHONE_NUMBER => '0123456789',
                ],
            ],
        ], $struct->getSalesChannelContext()->getContext());

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => $this->getValidRequestAction(),
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PDD,
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'bankaccountholder' => 'Max Mustermann',
                'iban' => 'DE85500105173716329595',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }

    public function testItAddsCorrectAuthorizeParametersWithSavedBirthday(): void
    {
        $dataBag = new RequestDataBag([
            'securedDirectDebitPhone' => '0123456789',
            'securedDirectDebitIban' => 'DE85500105173716329595',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        // Save phone number on customer custom fields
        $this->getContainer()->get('customer.repository')->update([
            [
                'id' => $struct->getPaymentTransaction()->getOrder()->getOrderCustomer()->getCustomerId(),
                'customFields' => [
                    CustomFieldInstaller::CUSTOMER_BIRTHDAY => '2000-01-01',
                ],
            ],
        ], $struct->getSalesChannelContext()->getContext());

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => $this->getValidRequestAction(),
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PDD,
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'bankaccountholder' => 'Max Mustermann',
                'iban' => 'DE85500105173716329595',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }

    protected function getParameterBuilder(): string
    {
        return AuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneSecuredDirectDebitPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
