<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class TransactionStatusWebhookHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    /** @var MockObject&OrderTransactionStateHandler */
    private $transactionStateHandler;

    protected function setUp(): void
    {
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
    }

    public function testCreditcardAppointed(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $this->transactionStateHandler->expects($this->once())->method('reopen')->with(Constants::ORDER_TRANSACTION_ID);

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $this->createMock(EntityRepositoryInterface::class),
            $this->transactionStateHandler,
            new TransactionDataHandler(new EntityRepositoryMock()),
            []
        );
        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService
        );
        $transactionStatusHandler->process(
            $salesChannelContext,
            [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => 'appointed',
                'sequencenumber' => '0',
            ]
        );
    }

    public function testCreditcardAppointedWithMapping(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $statusRepository           = $this->createMock(EntityRepositoryInterface::class);
        $dataHandler                = $this->createTestProxy(TransactionDataHandler::class, [new EntityRepositoryMock()]);
        $orderTransactionRepository = $this->createMock(EntityRepositoryInterface::class);
        $transactionStatusService   = $this->createTestProxy(TransactionStatusService::class, [
            $orderTransactionRepository,
            $this->transactionStateHandler,
            $dataHandler,
            new ConfigReaderMock([
                'paymentStatusAppointed' => 'test-state',
            ]),
            $statusRepository,
        ]);
        $stateEntity = new StateMachineStateEntity();
        $stateEntity->setId('test-state');
        $statusRepository->expects($this->once())->method('search')->willReturn(
            new EntitySearchResult(1, new EntityCollection([$stateEntity]), null, new Criteria(), Context::createDefaultContext())
        );
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId('test-transaction');
        $orderTransactionRepository->expects($this->once())->method('search')->willReturn(
            new EntitySearchResult(1, new EntityCollection([$orderTransactionEntity]), null, new Criteria(), Context::createDefaultContext())
        );
        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService
        );

        $dataHandler->expects($this->once())->method('saveTransactionState')->with('test-state');

        $transactionStatusHandler->process(
            $salesChannelContext,
            [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => 'appointed',
                'sequencenumber' => '0',
            ]
        );
    }
}
