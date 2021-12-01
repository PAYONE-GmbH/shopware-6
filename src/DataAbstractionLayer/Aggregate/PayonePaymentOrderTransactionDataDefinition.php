<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentOrderTransactionDataDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'payone_payment_order_transaction_data';
    }

    public function getEntityClass(): string
    {
        return PayonePaymentOrderTransactionDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentOrderTransactionDataCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
                (new FkField('order_transaction_id', 'orderTransactionId', OrderTransactionDefinition::class))->addFlags(new Required()),
                new StringField('transaction_id', 'transactionId', 255),
                new JsonField('transaction_data', 'transactionData', [], null),
                new IntField('sequence_number', 'sequenceNumber'),
                new StringField('transaction_state', 'transactionState', 255),
                new StringField('user_id', 'userId', 255),
                new StringField('last_request', 'lastRequest', 255),
                new BoolField('allow_capture', 'allowCapture'),
                new IntField('captured_amount', 'capturedAmount', null, null),
                new BoolField('allow_refund', 'allowRefund'),
                new IntField('refunded_amount', 'refundedAmount', null, null),
                new StringField('mandate_identification', 'mandateIdentification', 255),
                new StringField('authorization_type', 'authorizationType', 255),
                new StringField('work_order_id', 'workOrderId', 255),
                new StringField('clearing_reference', 'clearingReference', 255),
                new StringField('clearing_type', 'clearingType', 255),
                new StringField('financing_type', 'financingType', 255),
                new StringField('capture_mode', 'captureMode', 255),
                new JsonField('clearing_bank_account', 'clearingBankAccount', [], null),

                new OneToOneAssociationField('orderTransaction', 'order_transaction_id', 'id', OrderTransactionDefinition::class, false),
            ]
        );
    }
}
