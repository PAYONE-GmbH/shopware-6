<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\Transaction;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Struct\PaymentTransaction;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class TransactionDataHandlerTest extends TestCase
{
    public function testItSavesTransactionDataWithoutExistingTransactionExtension(): void
    {
        $newTransactionData = [
            'sequenceNumber' => 1,
        ];

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->expects(static::atLeastOnce())
            ->method('getExtension')
            ->with(static::equalTo(PayonePaymentOrderTransactionExtension::NAME))
            ->willReturn(null);
        $orderTransaction->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('the-id');

        $transactionRepository = $this->createMock(EntityRepositoryInterface::class);
        $transactionRepository->expects(static::once())
            ->method('upsert')
            ->with(static::callback(static function (array $data) {
                $transaction = $data[0];
                static::assertSame('the-id', $transaction['id']);
                static::assertSame(1, $transaction[PayonePaymentOrderTransactionExtension::NAME]['sequenceNumber']);
                static::assertArrayNotHasKey('id', $transaction[PayonePaymentOrderTransactionExtension::NAME]);

                return true;
            }));

        $transactionDataHandler = new TransactionDataHandler(
            $transactionRepository,
            $this->createMock(CurrencyPrecisionInterface::class)
        );

        $transaction = PaymentTransaction::fromOrderTransaction(
            $orderTransaction,
            $this->createMock(OrderEntity::class)
        );

        $transactionDataHandler->saveTransactionData(
            $transaction,
            $this->createMock(Context::class),
            $newTransactionData
        );
    }

    public function testItSavesTransactionDataWithExistingTransactionExtension(): void
    {
        $newTransactionData = [
            'sequenceNumber' => 1,
        ];

        $extensionEntity = new PayonePaymentOrderTransactionDataEntity();
        $extensionEntity->setId('the-extension-id');

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->expects(static::atLeastOnce())
            ->method('getExtension')
            ->with(static::equalTo(PayonePaymentOrderTransactionExtension::NAME))
            ->willReturn($extensionEntity);
        $orderTransaction->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('the-transaction-id');

        $transactionRepository = $this->createMock(EntityRepositoryInterface::class);
        $transactionRepository->expects(static::once())
            ->method('upsert')
            ->with(static::callback(static function (array $data) {
                $transaction = $data[0];
                static::assertSame('the-transaction-id', $transaction['id']);
                static::assertSame('the-extension-id', $transaction[PayonePaymentOrderTransactionExtension::NAME]['id']);
                static::assertSame(1, $transaction[PayonePaymentOrderTransactionExtension::NAME]['sequenceNumber']);

                return true;
            }));

        $transactionDataHandler = new TransactionDataHandler(
            $transactionRepository,
            $this->createMock(CurrencyPrecisionInterface::class)
        );

        $transaction = PaymentTransaction::fromOrderTransaction(
            $orderTransaction,
            $this->createMock(OrderEntity::class)
        );

        $transactionDataHandler->saveTransactionData(
            $transaction,
            $this->createMock(Context::class),
            $newTransactionData
        );
    }
}
