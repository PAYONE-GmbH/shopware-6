<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\Profile;

class Profile
{
    /** @var int */
    private $shopId;

    /** @var array */
    private $configuration;

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function setShopId(int $shopId): void
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
