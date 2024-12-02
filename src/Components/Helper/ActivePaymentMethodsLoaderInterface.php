<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ActivePaymentMethodsLoaderInterface
{
    public function getActivePaymentMethodIds(SalesChannelContext $salesChannelContext): array;

    public function clearCache(Context $context): void;
}
