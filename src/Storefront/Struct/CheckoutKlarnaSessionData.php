<?php

namespace PayonePayment\Storefront\Struct;

class CheckoutKlarnaSessionData
{

    private string $clientToken;
    private string $workOrderId;
    private string $paymentMethodIdentifier;
    private string $cartHash;

    public function __construct(
        string $clientToken,
        string $workOrderId,
        string $paymentMethodIdentifier,
        string $cartHash
    )
    {
        $this->clientToken = $clientToken;
        $this->workOrderId = $workOrderId;
        $this->paymentMethodIdentifier = $paymentMethodIdentifier;
        $this->cartHash = $cartHash;
    }

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getPaymentMethodIdentifier(): string
    {
        return $this->paymentMethodIdentifier;
    }

    public function getCartHash(): string
    {
        return $this->cartHash;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
