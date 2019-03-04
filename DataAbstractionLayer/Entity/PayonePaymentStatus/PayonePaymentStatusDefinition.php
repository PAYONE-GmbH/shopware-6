<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentStatus;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;

class PayonePaymentStatusDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'payone_payment_status';
    }

    public static function getCollectionClass(): string
    {
        return PayonePaymentStatusCollection::class;
    }

    public static function getEntityClass(): string
    {
        return PayonePaymentStatusEntity::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new FkField('order_transaction_id', 'orderTransactionId', OrderTransactionDefinition::class),
            new IntField('sequence_number', 'sequenceNumber'),
            new StringField('action', 'action'),
            new StringField('reference', 'reference'),
            new StringField('clearing_type', 'clearingType'),
            new FloatField('price', 'price'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
