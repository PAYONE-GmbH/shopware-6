<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Extension\Order;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderExtension implements EntityExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendFields(FieldCollection $collection): void
    {
        $test = $collection->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
