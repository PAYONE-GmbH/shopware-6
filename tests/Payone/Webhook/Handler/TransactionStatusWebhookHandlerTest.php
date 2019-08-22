<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

class TransactionStatusWebhookHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    private const CURRENCY_ID           = '9d185b6a82224319a326a0aed4f80d0a';
    private const ORDER_ID              = 'c23b44f2778240c7ad09bee356004503';
    private const ORDER_TRANSACTION_ID  = '4c8a04d0ae374bdbac305d717cdaf9c6';
    private const PAYONE_TRANSACTION_ID = 'test-transaction-id';

    /** @var OrderTransactionStateHandler */
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

        $this->transactionStateHandler->expects($this->once())->method('open')->with(self::ORDER_TRANSACTION_ID);

        $this->createHandler()->process($salesChannelContext, [
            'txid'           => self::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ]);
    }

    private function createHandler(): TransactionStatusWebhookHandler
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(self::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(self::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(self::CURRENCY_ID);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => self::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
            CustomFieldInstaller::LAST_REQUEST    => 'authorization',
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $orderTransactionRepository = $this->createMock(EntityRepository::class);
        $orderTransactionRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$orderTransactionEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        return new TransactionStatusWebhookHandler(
            new TransactionStatusService(
                $orderTransactionRepository,
                $this->transactionStateHandler,
                new TransactionDataHandler(new EntityRepositoryMock()),
                new ConfigReaderMock(),
                new EntityRepositoryMock()
            ),
            new NullLogger()
        );
    }
}
