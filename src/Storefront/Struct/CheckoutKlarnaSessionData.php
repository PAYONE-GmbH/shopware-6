<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

class CheckoutKlarnaSessionData extends CheckoutCartPaymentData
{
    public function __construct(
        private readonly string $clientToken,
        string $workOrderId,
        private readonly string $paymentMethodIdentifier,
        string $cartHash
    ) {
        $this->workOrderId = $workOrderId;
        $this->cartHash = $cartHash;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function getPaymentMethodIdentifier(): string
    {
        return $this->paymentMethodIdentifier;
    }
}
