<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationForward;

use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentNotificationForwardDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'payone_payment_notification_forward';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentNotificationForwardCollection::class;
    }

    public function getEntityClass(): string
    {
        return PayonePaymentNotificationForwardEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            new FkField('notification_target_id', 'notificationTargetId', PayonePaymentNotificationTargetDefinition::class),
            new OneToOneAssociationField('notificationTarget', 'notification_target_id', 'id', PayonePaymentNotificationTargetDefinition::class, true),

            (new StringField('ip', 'ip')),
            (new StringField('txaction', 'txaction')),
            (new StringField('response', 'response')),

            new FkField('transaction_id', 'transactionId', OrderTransactionDefinition::class),
            new OneToOneAssociationField('transaction', 'transaction_id', 'id', OrderTransactionDefinition::class, false),

            (new LongTextField('content', 'content')),
        ]);
    }
}
