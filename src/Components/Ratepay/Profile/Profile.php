<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\Profile;

class Profile
{
    /** @var string */
    private $shopId;

    /** @var array */
    private $configuration;

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function setShopId(string $shopId): void
    {
        $this->shopId = $shopId;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
