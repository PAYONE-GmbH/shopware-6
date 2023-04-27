<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

class RatepayProfileStruct extends AbstractRequestParameterStruct
{
    public function __construct(
        protected string $shopId,
        protected string $currency,
        protected string $salesChannelId,
        string $paymentMethod,
        string $action = ''
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->action = $action;
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
