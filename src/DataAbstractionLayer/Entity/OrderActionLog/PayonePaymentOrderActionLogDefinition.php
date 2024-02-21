<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\OrderActionLog;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentOrderActionLogDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'payone_payment_order_action_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentOrderActionLogCollection::class;
    }

    public function getEntityClass(): string
    {
        return PayonePaymentOrderActionLogEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(OrderDefinition::class, 'order_version_id'))->addFlags(new Required()),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),

            (new StringField('transaction_id', 'transactionId'))->setFlags(new Required()),
            (new StringField('reference_number', 'referenceNumber'))->setFlags(new Required()),
            (new StringField('request', 'request'))->setFlags(new Required()),
            (new StringField('response', 'response'))->setFlags(new Required()),
            (new IntField('amount', 'amount'))->setFlags(new Required()),
            (new StringField('mode', 'mode'))->setFlags(new Required()),
            (new StringField('merchant_id', 'merchantId'))->setFlags(new Required()),
            (new StringField('portal_id', 'portalId'))->setFlags(new Required()),
            (new JsonField('request_details', 'requestDetails'))->setFlags(new Required()),
            (new JsonField('response_details', 'responseDetails'))->setFlags(new Required()),
            (new DateTimeField('request_date_time', 'requestDateTime'))->setFlags(new Required()),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
