<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInvoicing;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DeviceFingerprint\RatepayDeviceFingerprintService;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\RatepayInvoicing\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;
    use ConfigurationHelper;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), $this->getValidPaymentHandler());

        $request = $this->getRequestWithSession([
            RatepayDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'payonePhone' => '0123456789',
            'ratepayBirthday' => '2000-01-01',
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
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPV,
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[shop_id]' => '88880103',
                'add_paydata[device_token]' => 'the-device-ident-token',
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }

    public function testItThrowsExceptionOnMissingPhoneNumber(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), $this->getValidPaymentHandler());

        $request = $this->getRequestWithSession([
            RatepayDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'ratepayBirthday' => '2000-01-01',
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

    protected function getParameterBuilder(): string
    {
        return AuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneRatepayInvoicingPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
