<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ChecksCurrency
{
    /**
     * Checks the current currency matches the provided one.
     */
    protected function isCurrency(SalesChannelContext $context, string $currencyIso): bool
    {
        return $context->getCurrency()->getIsoCode() === $currencyIso;
    }
}
