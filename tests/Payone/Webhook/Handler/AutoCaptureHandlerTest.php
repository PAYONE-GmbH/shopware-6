<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureServiceInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Struct\PaymentTransaction;
use PHPUnit\Framework\TestCase;
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

        static::assertTrue($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => 'appointed']), 'supports should return true, cause of valid txid nd correct status');

        static::assertFalse($handler->supports($salesChannelMock, ['txaction' => 'appointed']), 'supports should return false, cause of missing txid');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1]), 'supports should return false, cause of missing status');
        static::assertFalse($handler->supports($salesChannelMock, []), 'supports should return false, cause of missing data');
        static::assertFalse($handler->supports($salesChannelMock, []), 'supports should return false, cause of missing data');

        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_CAPTURE]), 'supports should return false, cause of wrong status');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_COMPLETED]), 'supports should return false, cause of wrong status');
        static::assertFalse($handler->supports($salesChannelMock, ['txid' => 1, 'txaction' => TransactionStatusService::ACTION_FAILED]), 'supports should return false, cause of wrong status');
    }

    public function testIsCaptureGotExecuted(): void
    {
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn($this->createMock(PaymentTransaction::class));
        $captureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $captureService->expects(static::once())->method('captureIfPossible');

        $request = new Request();
        $request->request->set('txid', 123);

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
}
