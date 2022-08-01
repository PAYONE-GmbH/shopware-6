<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Test\TestCaseBase\CheckoutTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CaptureRequestParameterBuilderTest extends TestCase
{
    use CheckoutTestBehavior;

    public function testItAddsCorrectFullCaptureParameters(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'amount'          => 10000,
                'currency'        => 'EUR',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'txid'            => 'test-transaction-id',
            ],
            $parameters
        );
    }

    public function testItThrowsExceptionOnMissingTransactionId(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        unset($customFields[CustomFieldInstaller::TRANSACTION_ID]);
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnMissingSequenceNumber(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] = null;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnInvalidSequenceNumber(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] = -1;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItAddsParametersByCustomFields(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::WORK_ORDER_ID] = 123;
        $customFields[CustomFieldInstaller::CAPTURE_MODE] = CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED;
        $customFields[CustomFieldInstaller::CLEARING_TYPE] = AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC;
        $struct->getPaymentTransaction()->setCustomFields($customFields);

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'workorderid'          => 123,
                'capturemode'        => CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED,
                'clearingtype'     => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            ],
            $parameters
        );
    }

    public function testItAddsCorrectParametersForBancontact(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneBancontactPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::CAPTURE_MODE] = CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'capturemode'        => CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED,
                'settleaccount'        => CaptureRequestParameterBuilder::SETTLEACCOUNT_YES,
            ],
            $parameters
        );

        $customFields[CustomFieldInstaller::CAPTURE_MODE] = CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'capturemode'        => CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE,
                'settleaccount'        => CaptureRequestParameterBuilder::SETTLEACCOUNT_NO,
            ],
            $parameters
        );
    }

    public function testItAddsCaptureModeCompletedParameter(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => true,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertSame(CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED, $parameters['capturemode']);
    }

    public function testItAddsCaptureModeNotCompletedParameter(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => false,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertSame(CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE, $parameters['capturemode']);
    }

    public function testItAddsNullAsCaptureMode(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => true,
        ]);

        $struct     = $this->getFinancialTransactionStruct($dataBag, PayoneSofortBankingPaymentHandler::class);
        $builder    = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::LAST_REQUEST] = AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $parameters = $builder->getRequestParameter($struct);

        static::assertNull($parameters['capturemode']);
    }
}
