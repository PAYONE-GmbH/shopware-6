<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Struct\PaymentTransaction;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\Payone\Webhook\Handler\PaymentStatusHandler
 */
class PaymentStatusHandlerTest extends TestCase
{
    public function testSupports(): void
    {
        $handler = new PaymentStatusHandler(
            $this->createMock(TransactionDataHandlerInterface::class),
            $this->createMock(StateMachineRegistry::class),
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
        $stateMachine = $this->createMock(StateMachineRegistry::class);
        $stateMachine->expects(static::once())->method('transition');

        $request = new Request();
        $request->request->set('txid', 123);

        (new PaymentStatusHandler($dataHandler, $stateMachine))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }

    public function testIsCaptureGotNotExecutedIfTransactionIsNotFound(): void
    {
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->method('getPaymentTransactionByPayoneTransactionId')->willReturn(null);
        $stateMachine = $this->createMock(StateMachineRegistry::class);
        $stateMachine->expects(static::never())->method('transition');

        $request = new Request();
        $request->request->set('txid', 123);

        (new PaymentStatusHandler($dataHandler, $stateMachine))
            ->process($this->createMock(SalesChannelContext::class), $request);
    }
}
