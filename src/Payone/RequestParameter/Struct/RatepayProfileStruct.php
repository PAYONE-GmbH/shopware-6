<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

class RatepayProfileStruct extends AbstractRequestParameterStruct
{
    /** @var string */
    protected $shopId;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $salesChannelId;

    public function __construct(
        string $shopId,
        string $currency,
        string $salesChannelId,
        string $paymentMethod,
        string $action = ''
    ) {
        $this->shopId         = $shopId;
        $this->currency       = $currency;
        $this->salesChannelId = $salesChannelId;
        $this->paymentMethod  = $paymentMethod;
        $this->action         = $action;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }
}
