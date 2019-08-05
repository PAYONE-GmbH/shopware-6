<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface WebhookHandlerInterface
{
    public const RESPONSE_TSOK    = 'TSOK';
    public const RESPONSE_TSNOTOK = 'TSNOTOK';

    public function process(SalesChannelContext $salesChannelContext, array $data): void;

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool;
}
