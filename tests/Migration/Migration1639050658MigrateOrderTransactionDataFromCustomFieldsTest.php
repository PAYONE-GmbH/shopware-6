<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @covers \PayonePayment\Migration\Migration1639050658MigrateOrderTransactionDataFromCustomFields
 */
class Migration1639050658MigrateOrderTransactionDataFromCustomFieldsTest extends TestCase
{
    use PayoneTestBehavior;

    /**
     * @dataProvider customFieldExtensionMapping
     * @testdox It migrates the custom field $customFieldKey to entity extension
     */
    public function testItMigratesCustomFieldsToEntityExtensionWithSpecificFieldFilled(
        string $customFieldKey,
        $customFieldValue,
        string $extensionGetter,
        $expectedExtensionValue
    ): void {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order = $this->getRandomOrder($salesChannelContext);
        $orderTransaction = $order->getTransactions()->first();

        $customFields = [
            'payone_transaction_id' => 'the-transaction-id',
            $customFieldKey => $customFieldValue,
        ];

        $orderTransactionRepository = $this->getContainer()->get('order_transaction.repository');
        $orderTransactionRepository->update(
            [
                [
                    'id' => $orderTransaction->getId(),
                    'customFields' => $customFields,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $connection = $this->getContainer()->get(Connection::class);
        $migration = new Migration1639050658MigrateOrderTransactionDataFromCustomFields();

        $migration->update($connection);

        $payoneTransactionData = $this->getExtension($orderTransaction, $salesChannelContext->getContext());

        static::assertNotNull($payoneTransactionData);
        static::assertSame('the-transaction-id', $payoneTransactionData->getTransactionId());
        static::assertSame($expectedExtensionValue, $payoneTransactionData->$extensionGetter());

        $orderTransaction = $this->getOrderTransaction($orderTransaction->getId(), $salesChannelContext->getContext());
        $orderTransactionCustomFields = $orderTransaction->getCustomFields();

        static::assertArrayNotHasKey('payone_transaction_id', $orderTransactionCustomFields);
        static::assertArrayNotHasKey($customFieldKey, $orderTransactionCustomFields);
    }

    public function testItNotCreatesExtensionWithoutPayoneTransactionId(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order = $this->getRandomOrder($salesChannelContext);
        $orderTransaction = $order->getTransactions()->first();

        $customFields = [
            'some-field' => 'some-value',
        ];

        $orderTransactionRepository = $this->getContainer()->get('order_transaction.repository');
        $orderTransactionRepository->update(
            [
                [
                    'id' => $orderTransaction->getId(),
                    'customFields' => $customFields,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $connection = $this->getContainer()->get(Connection::class);
        $migration = new Migration1639050658MigrateOrderTransactionDataFromCustomFields();

        $migration->update($connection);

        $payoneTransactionData = $this->getExtension($orderTransaction, $salesChannelContext->getContext());

        static::assertNull($payoneTransactionData);

        $orderTransaction = $this->getOrderTransaction($orderTransaction->getId(), $salesChannelContext->getContext());
        $orderTransactionCustomFields = $orderTransaction->getCustomFields();

        static::assertArrayHasKey('some-field', $orderTransactionCustomFields);
    }

    public function customFieldExtensionMapping(): array
    {
        return [
            ['payone_transaction_data', ['key' => 'value'], 'getTransactionData', ['key' => 'value']],
            ['payone_sequence_number', 0, 'getSequenceNumber', 0],
            ['payone_transaction_state', 'the-transaction-state', 'getTransactionState', 'the-transaction-state'],
            ['payone_user_id', 'the-user-id', 'getUserId', 'the-user-id'],
            ['payone_last_request', 'the-last-request', 'getLastRequest', 'the-last-request'],
            ['payone_allow_capture', true, 'getAllowCapture', true],
            ['payone_allow_capture', '1', 'getAllowCapture', true],
            ['payone_allow_capture', false, 'getAllowCapture', false],
            ['payone_allow_capture', '0', 'getAllowCapture', false],
            ['payone_allow_refund', true, 'getAllowRefund', true],
            ['payone_allow_refund', '1', 'getAllowRefund', true],
            ['payone_allow_refund', false, 'getAllowRefund', false],
            ['payone_allow_refund', '0', 'getAllowRefund', false],
            ['payone_captured_amount', 0, 'getCapturedAmount', 0],
            ['payone_refunded_amount', 0, 'getCapturedAmount', 0], // Test if the default value of payone_captured_amount works
            ['payone_refunded_amount', 0, 'getRefundedAmount', 0],
            ['payone_captured_amount', 0, 'getRefundedAmount', 0], // Test if the default value of payone_refunded_amount works
            ['payone_mandate_identification', 'the-mandate-identification', 'getMandateIdentification', 'the-mandate-identification'],
            ['payone_authorization_type', 'the-authorization-type', 'getAuthorizationType', 'the-authorization-type'],
            ['payone_work_order_id', 'the-work-order-id', 'getWorkOrderId', 'the-work-order-id'],
            ['payone_clearing_reference', 'the-clearing-reference', 'getClearingReference', 'the-clearing-reference'],
            ['payone_clearing_type', 'the-clearing-type', 'getClearingType', 'the-clearing-type'],
            ['payone_financing_type', 'the-financing-type', 'getFinancingType', 'the-financing-type'],
            ['payone_capture_mode', 'the-capture-mode', 'getCaptureMode', 'the-capture-mode'],
            ['payone_clearing_bank_account', ['key' => 'value'], 'getClearingBankAccount', ['key' => 'value']],
            ['payone_used_ratepay_shop_id', 88880103, 'getAdditionalData', ['used_ratepay_shop_id' => '88880103']],
        ];
    }

    protected function getExtension(OrderTransactionEntity $orderTransaction, Context $context): ?PayonePaymentOrderTransactionDataEntity
    {
        $payoneTransactionDataRepository = $this->getContainer()->get('payone_payment_order_transaction_data.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderTransactionId', $orderTransaction->getId()));

        $result = $payoneTransactionDataRepository->search($criteria, $context);
        /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
        return $result->first();
    }

    protected function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $orderTransactionRepository = $this->getContainer()->get('order_transaction.repository');

        $criteria = new Criteria([$orderTransactionId]);

        $result = $orderTransactionRepository->search($criteria, $context);
        /** @var OrderTransactionEntity|null $orderTransaction */
        return $result->first();
    }
}
