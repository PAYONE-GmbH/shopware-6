<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\NotificationQueue;

use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentNotificationQueueDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'payone_payment_notification_queue';

    final public const STATUS_SCHEDULED = 'scheduled';

    final public const STATUS_FINISHED = 'finished';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentNotificationQueueCollection::class;
    }

    public function getEntityClass(): string
    {
        return PayonePaymentNotificationQueueEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new FkField('notification_target_id', 'notificationTargetId', PayonePaymentNotificationTargetDefinition::class),
            new OneToOneAssociationField('notificationTarget', 'notification_target_id', 'id', PayonePaymentNotificationTargetDefinition::class, true),
            new IntField('response_http_code', 'responseHttpCode'),
            (new LongTextField('message', 'message')),
            new DateTimeField('last_execution_time', 'lastExecutionTime'),
            (new DateTimeField('next_execution_time', 'nextExecutionTime'))->addFlags(new Required()),
        ]);
    }
}
