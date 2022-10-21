<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

class CheckoutKlarnaSessionData extends CheckoutCartPaymentData
{
    private string $clientToken;

    private string $paymentMethodIdentifier;

    public function __construct(
        string $clientToken,
        string $workOrderId,
        string $paymentMethodIdentifier,
        string $cartHash
    ) {
        $this->clientToken = $clientToken;
        $this->workOrderId = $workOrderId;
        $this->paymentMethodIdentifier = $paymentMethodIdentifier;
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
