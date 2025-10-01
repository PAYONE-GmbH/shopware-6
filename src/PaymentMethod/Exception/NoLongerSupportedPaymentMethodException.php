<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class NoLongerSupportedPaymentMethodException extends ShopwareHttpException
{
    public function __construct(string $name)
    {
        parent::__construct(
            'The payment method "{{ name }}" is no longer supported.',
            [ 'name' => $name ],
        );
    }

    public function getErrorCode(): string
    {
        return 'PAYONE__PAYMENT_METHOD_NO_LONGER_SUPPORTED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
