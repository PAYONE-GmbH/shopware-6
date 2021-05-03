<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PHPUnit\Framework\MockObject\Generator;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
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
        array $configuration = [],
        ?OrderTransactionEntity $transaction = null
    ): TransactionStatusServiceInterface {

        /** @var EntityRepositoryInterface $entityRepositoryMock */
        $entityRepositoryMock = (new Generator())->getMock(EntityRepositoryInterface::class);

        try {
            $entityRepositoryMock->method('search')->willReturn(
                new EntitySearchResult(
                    OrderTransactionEntity::class,
                    1,
                    new EntityCollection(array_filter([$transaction])),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );
        } catch(\Throwable $e) {
            $entityRepositoryMock->method('search')->willReturn(
                /** @phpstan-ignore-next-line */
                new EntitySearchResult(0, new EntityCollection(array_filter([$transaction])), null, new Criteria(), Context::createDefaultContext())
            );
        }

        $entityRepositoryMock->method('update')->willReturn(new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []));

        return new TransactionStatusService(
            $stateMachineRegistry,
            new ConfigReaderMock($configuration),
            $entityRepositoryMock,
            new NullLogger()
        );
    }
}
