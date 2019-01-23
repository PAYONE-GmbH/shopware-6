<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig;

use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class PayonePaymentConfigDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'payone_payment_config';
    }

    public static function getCollectionClass(): string
    {
        return PayonePaymentConfigCollection::class;
    }

    public static function getEntityClass(): string
    {
        return PayonePaymentConfigEntity::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),

            (new StringField('config_key', 'key'))->setFlags(new Required()),
            new StringField('config_value', 'value'),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
