<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase\Factory;

use PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureServiceInterface;
use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\TestCaseBase\Mock\ConfigReaderMock;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class TransactionStatusWebhookHandlerFactory
{
    public static function createHandler(
        TransactionStatusServiceInterface $transactionStatusService,
        TransactionDataHandlerInterface $transactionDataHandler,
        AutomaticCaptureServiceInterface $automaticCaptureService
    ): TransactionStatusWebhookHandler {
        return new TransactionStatusWebhookHandler(
            $transactionStatusService,
            $transactionDataHandler,
            new NullLogger(),
            $automaticCaptureService
        );
    }

    public static function createTransactionStatusService(
        StateMachineRegistry $stateMachineRegistry,
        array $configuration = [],
        ?OrderTransactionEntity $transaction = null
    ): TransactionStatusServiceInterface {
        /** @var MockObject $entityRepositoryMock */
        $entityRepositoryMock = (new Generator())->getMock(EntityRepository::class, [], [], '', false);

        try {
            $entitySearchResult = new EntitySearchResult(
                OrderTransactionEntity::class,
                1,
                new EntityCollection(array_filter([$transaction])),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(0, new EntityCollection(array_filter([$transaction])), null, new Criteria(), Context::createDefaultContext());
        }

        $entityRepositoryMock->method('search')->willReturn($entitySearchResult);
        $entityRepositoryMock->method('update')->willReturn(new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []));

        /** @var EntityRepository $entityRepositoryMock */
        return new TransactionStatusService(
            $stateMachineRegistry,
            new ConfigReaderMock($configuration),
            $entityRepositoryMock,
            new NullLogger(),
            new CurrencyPrecision()
        );
    }
}
