<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecuredInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DeviceFingerprint\PayoneBNPLDeviceFingerprintService;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SecuredInstallment\PreAuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $request = $this->getRequestWithSession([
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            RequestConstants::PHONE => '0123456789',
            RequestConstants::BIRTHDAY => '2000-01-01',
            'securedInstallmentIban' => 'DE85500105173716329595',
            'securedInstallmentOptionId' => 'the-option-id',
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
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIN,
                'add_paydata[device_token]' => 'the-device-ident-token',
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'bankaccountholder' => 'Max Mustermann',
                'iban' => 'DE85500105173716329595',
                'add_paydata[installment_option_id]' => 'the-option-id',
            ],
            $parameters
        );

        static::assertArrayHasKey('amount', $parameters);
        static::assertArrayHasKey('currency', $parameters);
    }

    protected function getParameterBuilder(): string
    {
        return PreAuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneSecuredInstallmentPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
