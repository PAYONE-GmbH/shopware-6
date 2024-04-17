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
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SecuredInvoice\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $request = $this->getRequestWithSession([
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'payonePhone' => '0123456789',
            'payoneBirthday' => '2000-01-01',
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

    public function testItThrowsExceptionOnMissingPhoneNumber(): void
    {
        $request = $this->getRequestWithSession([
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'payoneBirthday' => '2000-01-01',
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
        $request = $this->getRequestWithSession([
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'payonePhone' => '0123456789',
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

    public function testItAddsCorrectAuthorizeParametersForB2b(): void
    {
        $request = $this->getRequestWithSession([
            PayoneBNPLDeviceFingerprintService::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $dataBag = new RequestDataBag([
            'payonePhone' => '0123456789',
            'payoneBirthday' => '2000-01-01',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        // Save company and vat id on customer billing address
        $this->getContainer()->get('order_address.repository')->update([
            [
                'id' => $struct->getPaymentTransaction()->getOrder()->getBillingAddressId(),
                'company' => 'The Company',
                'vatId' => 'DE123456789',
            ],
        ], $struct->getSalesChannelContext()->getContext());

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => $this->getValidRequestAction(),
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIV,
                'telephonenumber' => '0123456789',
                'birthday' => '20000101',
                'it[1]' => LineItemHydrator::TYPE_GOODS,
                'businessrelation' => 'b2b',
                'company' => 'The Company',
                'vatid' => 'DE123456789',
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
        return PayoneSecuredInvoicePaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
