<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait NonRedirectResponseTrait
{
    public function getRedirectResponse(
        SalesChannelContext $context,
        array $request,
        array $response,
    ): RedirectResponse|null {
        return null;
    }
}
