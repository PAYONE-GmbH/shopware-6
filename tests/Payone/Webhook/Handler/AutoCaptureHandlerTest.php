<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureServiceInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\PaymentMethod\PayoneOpenInvoice;
use PayonePayment\PaymentMethod\PayonePrepayment;
use PayonePayment\Struct\PaymentTransaction;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\Payone\Webhook\Handler\AutoCaptureHandler
 */
class AutoCaptureHandlerTest extends TestCase
{
    public function testSupports(): void
    {
        $handler = new AutoCaptureHandler(
            $this->createMock(TransactionDataHandlerInterface::class),
            $this->createMock(AutomaticCaptureServiceInterface::class),
        );

        $salesChannelMock = $this->createMock(SalesChannelContext::class);

        static::assertTrue($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => 'paid']), 'supports should return true, cause of valid txid and correct status');
        static::assertTrue($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => 'appointed']), 'supports should return true, cause of valid txid and correct status');

        static::assertFalse($handler->supports($salesChannelMock, ['txaction' => 'paid']), 'supports should return false, cause of missing txid');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1]), 'supports should return false, cause of missing status');
        static::assertFalse($handler->supports($salesChannelMock, []), 'supports should return false, cause of missing data');
        static::assertFalse($handler->supports($salesChannelMock, []), 'supports should return false, cause of missing data');

        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_CAPTURE]), 'supports should return false, cause of wrong status');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_COMPLETED]), 'supports should return false, cause of wrong status');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_FAILED]), 'supports should return false, cause of wrong status');
    }

    public function testIsCaptureGotExecutedForPrepayment(): void
    {
        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getPaymentMethodId')->willReturn(PayonePrepayment::UUID);
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->method('getOrderTransaction')->willReturn($orderTransaction);
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::once())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);
        $request->request->set('txaction', TransactionStatusService::ACTION_PAID);

        (new AutoCaptureHandler($dataHandler, $captureService))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }

    public function testIsCaptureGotExecutedForOpenInvoice(): void
    {
        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getPaymentMethodId')->willReturn(PayoneOpenInvoice::UUID);
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->method('getOrderTransaction')->willReturn($orderTransaction);
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::once())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);
        $request->request->set('txaction', TransactionStatusService::ACTION_APPOINTED);

        (new AutoCaptureHandler($dataHandler, $captureService))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }

    public function testIsCaptureGotNotExecutedIfTransactionIsNotFound(): void
    {
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn(null);
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::never())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);

        (new AutoCaptureHandler($dataHandler, $captureService))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }

    public function testIsCaptureGotNotExecutedForPrepaymentIfActionIsAppointed(): void
    {
        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getPaymentMethodId')->willReturn(PayonePrepayment::UUID);
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->method('getOrderTransaction')->willReturn($orderTransaction);
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::never())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);
        $request->request->set('txaction', TransactionStatusService::ACTION_APPOINTED);

        (new AutoCaptureHandler($dataHandler, $captureService))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }

    public function testIsCaptureGotNotExecutedForOpenInvoiceIfActionIsPaid(): void
    {
        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getPaymentMethodId')->willReturn(PayoneOpenInvoice::UUID);
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->method('getOrderTransaction')->willReturn($orderTransaction);
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::never())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);
        $request->request->set('txaction', TransactionStatusService::ACTION_PAID);

        (new AutoCaptureHandler($dataHandler, $captureService))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }
}
