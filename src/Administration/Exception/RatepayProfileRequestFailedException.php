<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RatepayProfileRequestFailedException extends ShopwareHttpException
{
    public function __construct(int $shopId, string $currency, string $errorMessage)
    {
        parent::__construct(
            'Failed to load the profile configuration with Shop ID "{{ shopId }}" and currency "{{ currency }}".',
            [
                'shopId'       => $shopId,
                'currency'     => $currency,
                'errorMessage' => $errorMessage,
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'PAYONE__RATEPAY_PROFILE_REQUEST_FAILED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
