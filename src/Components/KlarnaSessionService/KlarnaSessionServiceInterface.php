<?php

declare(strict_types=1);

namespace PayonePayment\Components\KlarnaSessionService;

use PayonePayment\Storefront\Struct\CheckoutKlarnaSessionData;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface KlarnaSessionServiceInterface
{
    public function createKlarnaSession(SalesChannelContext $salesChannelContext, ?string $orderId = null): CheckoutKlarnaSessionData;
}
