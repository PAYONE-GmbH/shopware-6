<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecuredInvoice;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DeviceFingerprint\PayoneBNPLDeviceFingerprintService;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SecuredInvoice\PreAuthorizeRequestParameterBuilder
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
            'payonePhone' => '0123456789',
            'payoneInvoiceBirthday' => '2000-01-01',
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
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIV,
                'add_paydata[device_token]' => 'the-device-ident-token',
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
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
        return PayoneSecuredInvoicePaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
