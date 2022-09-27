<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Capture\CaptureRequestParameterBuilder
 */
class CaptureRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItSupportsCaptureRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsRefundRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsPaymentRequests(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectFullCaptureParameters(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE,
                'amount' => 10000,
                'currency' => 'EUR',
                'sequencenumber' => 1,
                'txid' => 'test-transaction-id',
            ],
            $parameters
        );
    }

    public function testItThrowsExceptionOnMissingTransactionData(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $struct->getPaymentTransaction()->getOrderTransaction()->removeExtension(PayonePaymentOrderTransactionExtension::NAME);

        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnMissingSequenceNumber(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->sequenceNumber = null;
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnInvalidSequenceNumber(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->sequenceNumber = -1;
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItAddsParametersByExtensionFields(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->workOrderId = '123';
        $extension->captureMode = CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED;
        $extension->clearingType = AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC;
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'workorderid' => 123,
                'capturemode' => CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED,
                'clearingtype' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            ],
            $parameters
        );
    }

    public function testItAddsCorrectParametersForBancontact(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneBancontactPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->captureMode = CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED;
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'capturemode' => CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED,
                'settleaccount' => CaptureRequestParameterBuilder::SETTLEACCOUNT_YES,
            ],
            $parameters
        );

        $extension->captureMode = CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE;
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'capturemode' => CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE,
                'settleaccount' => CaptureRequestParameterBuilder::SETTLEACCOUNT_NO,
            ],
            $parameters
        );
    }

    public function testItAddsCorrectParametersForRatepay(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneRatepayDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->additionalData = ['used_ratepay_shop_id' => '88880103'];
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('yes', $parameters['settleaccount']);
        static::assertSame('88880103', $parameters['add_paydata[shop_id]']);
    }

    public function testItAddsCaptureModeCompletedParameter(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => true,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertSame(CaptureRequestParameterBuilder::CAPTUREMODE_COMPLETED, $parameters['capturemode']);
    }

    public function testItAddsCaptureModeNotCompletedParameter(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => false,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertSame(CaptureRequestParameterBuilder::CAPTUREMODE_INCOMPLETE, $parameters['capturemode']);
    }

    public function testItAddsNullAsCaptureMode(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
            'complete' => true,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneSofortBankingPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CaptureRequestParameterBuilder::class);
        $transactionData = $struct->getPaymentTransaction()->getPayoneTransactionData();
        $transactionData['lastRequest'] = AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
        $struct->getPaymentTransaction()->setPayoneTransactionData($transactionData);

        $parameters = $builder->getRequestParameter($struct);

        static::assertNull($parameters['capturemode']);
    }
}
