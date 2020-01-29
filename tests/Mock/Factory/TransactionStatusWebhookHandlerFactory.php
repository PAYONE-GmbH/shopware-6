<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use Psr\Log\NullLogger;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class TransactionStatusWebhookHandlerFactory
{
    public static function createHandler(
        TransactionStatusServiceInterface $transactionStatusService,
        TransactionDataHandlerInterface $transactionDataHandler
    ): TransactionStatusWebhookHandler {
        return new TransactionStatusWebhookHandler(
            $transactionStatusService,
            $transactionDataHandler,
            new NullLogger()
        );
    }

    public static function createTransactionStatusService(
        StateMachineRegistry $stateMachineRegistry,
        array $configuration = []
    ): TransactionStatusServiceInterface {
        return new TransactionStatusService(
            $stateMachineRegistry,
            new ConfigReaderMock($configuration)
        );
    }
}
