<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;

/**
 * @covers \PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity
 */
class PayonePaymentOrderTransactionDataEntityTest extends TestCase
{
    public function testItSetsAndReturnsTransactionId(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'transactionId' => 'the-transaction-id',
        ]);
        static::assertSame('the-transaction-id', $entity->getTransactionId());
    }

    public function testItSetsAndReturnsOrderTransactionId(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'orderTransactionId' => 'the-order-transaction-id',
        ]);
        static::assertSame('the-order-transaction-id', $entity->getOrderTransactionId());
    }

    public function testItSetsAndReturnsOrderTransaction(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'orderTransaction' => $orderTransaction,
        ]);
        static::assertSame($orderTransaction, $entity->getOrderTransaction());
    }

    public function testItSetsAndReturnsTransactionData(): void
    {
        $transactionData = ['the-key' => 'the-value'];
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'transactionData' => $transactionData,
        ]);
        static::assertSame($transactionData, $entity->getTransactionData());
    }

    public function testItSetsAndReturnsSequenceNumber(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'sequenceNumber' => 0,
        ]);
        static::assertSame(0, $entity->getSequenceNumber());
    }

    public function testItSetsAndReturnsTransactionState(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'transactionState' => 'the-transaction-state',
        ]);
        static::assertSame('the-transaction-state', $entity->getTransactionState());
    }

    public function testItSetsAndReturnsUserId(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'userId' => 'the-user-id',
        ]);
        static::assertSame('the-user-id', $entity->getUserId());
    }

    public function testItSetsAndReturnsLastRequest(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'lastRequest' => 'the-last-request',
        ]);
        static::assertSame('the-last-request', $entity->getLastRequest());
    }

    public function testItSetsAndReturnsAllowCapture(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'allowCapture' => true,
        ]);
        static::assertTrue($entity->getAllowCapture());
    }

    public function testItSetsAndReturnsAllowRefund(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'allowRefund' => true,
        ]);
        static::assertTrue($entity->getAllowRefund());
    }

    public function testItSetsAndReturnsCapturedAmount(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'capturedAmount' => 1,
        ]);
        static::assertSame(1, $entity->getCapturedAmount());
    }

    public function testItSetsAndReturnsRefundedAmount(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'refundedAmount' => 1,
        ]);
        static::assertSame(1, $entity->getRefundedAmount());
    }

    public function testItSetsAndReturnsMandateIdentification(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'mandateIdentification' => 'the-mandate-identification',
        ]);
        static::assertSame('the-mandate-identification', $entity->getMandateIdentification());
    }

    public function testItSetsAndReturnsAuthorizationType(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'authorizationType' => 'the-authorization-type',
        ]);
        static::assertSame('the-authorization-type', $entity->getAuthorizationType());
    }

    public function testItSetsAndReturnsClearingReference(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'clearingReference' => 'the-clearing-reference',
        ]);
        static::assertSame('the-clearing-reference', $entity->getClearingReference());
    }

    public function testItSetsAndReturnsClearingType(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'clearingType' => 'the-clearing-type',
        ]);
        static::assertSame('the-clearing-type', $entity->getClearingType());
    }

    public function testItSetsAndReturnsFinancingType(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'financingType' => 'the-financing-type',
        ]);
        static::assertSame('the-financing-type', $entity->getFinancingType());
    }

    public function testItSetsAndReturnsCaptureMode(): void
    {
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'captureMode' => 'the-capture-mode',
        ]);
        static::assertSame('the-capture-mode', $entity->getCaptureMode());
    }

    public function testItSetsAndReturnsClearingBankAccount(): void
    {
        $clearingBankAccount = ['the-key' => 'the-value'];
        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign([
            'clearingBankAccount' => $clearingBankAccount,
        ]);
        static::assertSame($clearingBankAccount, $entity->getClearingBankAccount());
    }

    public function testItSetsAndReturnsAdditionalData(): void
    {
        $additionalData = ['the-key' => 'the-value'];
        $entity = new PayonePaymentOrderTransactionDataEntity();

        static::assertSame([], $entity->getAdditionalData());

        $entity->assign([
            'additionalData' => $additionalData,
        ]);
        static::assertSame($additionalData, $entity->getAdditionalData());
    }

    public function testItSerializesTheEntityToArray(): void
    {
        $entityData = [
            'id' => 'the-id',
            'transactionId' => 'the-transaction-id',
            'transactionData' => ['the-key' => 'the-value'],
            'sequenceNumber' => 0,
            'transactionState' => 'the-transaction-state',
            'userId' => 'the-user-id',
            'lastRequest' => 'the-last-request',
            'allowCapture' => true,
            'capturedAmount' => 1,
            'allowRefund' => true,
            'refundedAmount' => 1,
            'mandateIdentification' => 'the-mandate-identification',
            'authorizationType' => 'the-authorization-type',
            'clearingReference' => 'the-clearing-reference',
            'clearingType' => 'the-clearing-type',
            'financingType' => 'the-financing-type',
            'captureMode' => 'the-capture-mode',
            'clearingBankAccount' => ['the-key' => 'the-value'],
            'additionalData' => ['the-key' => 'the-value'],
        ];

        $entity = new PayonePaymentOrderTransactionDataEntity();
        $entity->assign($entityData);

        Assert::assertArraySubset($entityData, $entity->jsonSerialize());
    }
}
