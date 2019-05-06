<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class PayonePaymentConfigDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'payone_payment_config';
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentConfigCollection::class;
    }

    public function getEntityClass(): string
    {
        return PayonePaymentConfigEntity::class;
    }

    protected function defineFields(): FieldCollection
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
