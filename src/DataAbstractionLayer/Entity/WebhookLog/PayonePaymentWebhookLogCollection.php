<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\WebhookLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PayonePaymentWebhookLogEntity>
 */
class PayonePaymentWebhookLogCollection extends EntityCollection
{
    #[\Override]
    protected function getExpectedClass(): string
    {
        return PayonePaymentWebhookLogEntity::class;
    }
}
