<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DeviceFingerprint\RatepayDeviceFingerprintService;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment\PreAuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;
    use ConfigurationHelper;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $this->getValidPaymentHandler(),
            [
                'tx-limit-installment-min' => '10',
            ]
        );

        $request = $this->getRequestWithSession([
            RatepayDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'ratepayIban' => 'DE81500105177147426471',
            RequestConstants::PHONE => '0123456789',
            RequestConstants::BIRTHDAY => '2000-01-01',
            'ratepayInstallmentAmount' => '100',
            'ratepayInstallmentNumber' => '24',
            'ratepayLastInstallmentAmount' => '101',
            'ratepayInterestRate' => '10',
            'ratepayTotalAmount' => '1000',
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
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'iban' => 'DE81500105177147426471',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[shop_id]' => '88880103',
                'add_paydata[device_token]' => 'the-device-ident-token',
                'add_paydata[installment_amount]' => 10000,
                'add_paydata[installment_number]' => 24,
                'add_paydata[last_installment_amount]' => 10100,
                'add_paydata[interest_rate]' => 1000,
                'add_paydata[amount]' => 100000,
                'add_paydata[debit_paytype]' => 'DIRECT-DEBIT',
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
            ],
            $parameters
        );
    }

    protected function getParameterBuilder(): string
    {
        return PreAuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneRatepayInstallmentPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
