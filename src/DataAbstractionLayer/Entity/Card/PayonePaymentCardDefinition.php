<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\Card;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentCardDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'payone_payment_card';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PayonePaymentCardCollection::class;
    }

    public function getEntityClass(): string
    {
        return PayonePaymentCardEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        $pseudoCardPanField = (new StringField('pseudo_card_pan', 'pseudoCardPan'))->setFlags(new Required());

        if (class_exists(ApiAware::class)) {
            $pseudoCardPanField = (new StringField('pseudo_card_pan', 'pseudoCardPan'))->setFlags(new Required(), new ApiAware());
        }

        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),

            $pseudoCardPanField,
            (new StringField('truncated_card_pan', 'truncatedCardPan'))->setFlags(new Required()),
            (new DateTimeField('expires_at', 'expiresAt'))->setFlags(new Required()),

            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
