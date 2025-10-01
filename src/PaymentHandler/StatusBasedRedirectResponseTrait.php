<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait StatusBasedRedirectResponseTrait
{
    public function getRedirectResponse(
        SalesChannelContext $context,
        array $request,
        array $response,
    ): RedirectResponse {
        if ('redirect' === \strtolower((string) $response['status'])) {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }
}
