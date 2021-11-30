<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Extension;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PayonePaymentOrderTransactionExtension extends EntityExtension {
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField('payonePaymentOrderTransactionData', 'id', 'order_transaction_id', PayonePaymentOrderTransactionDataDefinition::class)
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderTransactionDefinition::class;
    }
}

