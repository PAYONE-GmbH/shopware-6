<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Extension\Order;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderTransactionExtension implements EntityExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new IntField('payone_transaction_id', 'payoneTransactionId')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionClass(): string
    {
        return OrderTransactionDefinition::class;
    }
}
