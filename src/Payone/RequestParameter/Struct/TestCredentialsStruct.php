<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use Shopware\Core\Framework\Struct\Struct;

class TestCredentialsStruct extends Struct
{
    /** @var string */
    protected $action = '';

    /** @var string */
    protected $paymentMethod;

    /** @var string */
    protected $salesChannelId;

    public function __construct(
        string $paymentMethod,
        string $action = '',
        string $salesChannelId
    ) {
        $this->paymentMethod  = $paymentMethod;
        $this->action         = $action;
        $this->salesChannelId = $salesChannelId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }
}
