<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

class CheckoutKlarnaSessionData
{
    /**
     * @var string
     */
    private $clientToken;

    /**
     * @var string
     */
    private $workOrderId;

    /**
     * @var string
     */
    private $paymentMethodIdentifier;

    /**
     * @var string
     */
    private $cartHash;

    public function __construct(
        string $clientToken,
        string $workOrderId,
        string $paymentMethodIdentifier,
        string $cartHash
    ) {
        $this->clientToken             = $clientToken;
        $this->workOrderId             = $workOrderId;
        $this->paymentMethodIdentifier = $paymentMethodIdentifier;
        $this->cartHash                = $cartHash;
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

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
