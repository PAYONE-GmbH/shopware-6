<?php

declare(strict_types=1);

namespace PayonePayment\Components\KlarnaSessionService;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Storefront\Struct\CheckoutKlarnaSessionData;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface KlarnaSessionServiceInterface
{
    /**
     * @throws PayoneRequestException
     */
    public function createKlarnaSession(SalesChannelContext $salesChannelContext, ?string $orderId = null): CheckoutKlarnaSessionData;
}
