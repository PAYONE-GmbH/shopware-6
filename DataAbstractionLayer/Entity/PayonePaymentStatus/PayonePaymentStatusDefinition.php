<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentStatus;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
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

    public static function getParentDefinitionClass(): ?string
    {
        return OrderDefinition::class;
    }

    /**
     * TODO: define fields for the status related informations if needed (remove classes otherwise)
     */
    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
