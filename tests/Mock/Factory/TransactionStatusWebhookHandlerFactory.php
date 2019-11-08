<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class TransactionStatusWebhookHandlerFactory
{
    public static function createHandler(
        TransactionStatusServiceInterface $transactionStatusService
    ): TransactionStatusWebhookHandler {
        return new TransactionStatusWebhookHandler(
            $transactionStatusService,
            new NullLogger()
        );
    }

    /**
     * @param EntityRepositoryInterface&MockObject $orderTransactionRepository
     */
    public static function createTransactionStatusService(
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        TransactionDataHandler $transactionDataHandler,
        array $configuration = []
    ): TransactionStatusServiceInterface {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID     => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER    => 0,
            CustomFieldInstaller::LAST_REQUEST       => 'authorization',
            CustomFieldInstaller::AUTHORIZATION_TYPE => 'authorization',
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $orderTransactionRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$orderTransactionEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        return new TransactionStatusService(
            $orderTransactionRepository,
            $transactionStateHandler,
            $transactionDataHandler,
            new ConfigReaderMock($configuration),
            new EntityRepositoryMock()
        );
    }
}
