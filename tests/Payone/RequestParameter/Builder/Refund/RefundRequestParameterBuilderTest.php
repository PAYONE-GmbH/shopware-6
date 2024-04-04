<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Refund;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Refund\RefundRequestParameterBuilder
 */
class RefundRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItSupportsRefundRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsCaptureRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsPaymentRequests(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectFullRefundParameters(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_DEBIT,
                'amount' => -10000,
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
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $struct->getPaymentTransaction()->getOrderTransaction()->removeExtension(PayonePaymentOrderTransactionExtension::NAME);

        if (class_exists(PaymentException::class)) {
            $this->expectException(PaymentException::class);
        } else {
            $this->expectException(InvalidOrderException::class);
        }

        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnMissingTransactionId(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->assign([
            'transactionId' => '',
        ]);
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        if (class_exists(PaymentException::class)) {
            $this->expectException(PaymentException::class);
        } else {
            $this->expectException(InvalidOrderException::class);
        }

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
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->assign([
            'sequenceNumber' => null,
        ]);
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        if (class_exists(PaymentException::class)) {
            $this->expectException(PaymentException::class);
        } else {
            $this->expectException(InvalidOrderException::class);
        }

        $builder->getRequestParameter($struct);
    }

    public function testItAddsCorrectRefundParametersForDebit(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->assign([
            'transactionData' => [
                [
                    'request' => [
                        'iban' => 'DE61500105178278794285',
                    ],
                ],
            ],
        ]);
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('DE61500105178278794285', $parameters['iban']);

        $extension->assign([
            'transactionData' => [[]],
        ]);
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        static::assertArrayNotHasKey('iban', $parameters);
    }

    public function testItAddsCorrectRefundParametersForRatepay(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneRatepayDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        /** @var PayonePaymentOrderTransactionDataEntity $extension */
        $extension = $struct->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $extension->assign([
            'additionalData' => ['used_ratepay_shop_id' => '88880103'],
        ]);
        $struct->getPaymentTransaction()->getOrderTransaction()->addExtension(PayonePaymentOrderTransactionExtension::NAME, $extension);

        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('yes', $parameters['settleaccount']);
        static::assertSame('88880103', $parameters['add_paydata[shop_id]']);
    }
}
