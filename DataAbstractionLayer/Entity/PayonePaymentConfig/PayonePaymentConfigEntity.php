<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class PayonePaymentConfigEntity extends Entity
{
    use EntityIdTrait;

    /** @var null|string */
    protected $salesChannelId;

    /** @var null|SalesChannelEntity */
    protected $salesChannel;

    /** @var string */
    protected $key;

    /** @var string */
    protected $value;

    /** @var null|DateTime */
    protected $createdAt;

    /** @var null|DateTime */
    protected $updatedAt;

    /**
     * @return null|string
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @return null|SalesChannelEntity
     */
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return null|DateTime
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return null|DateTime
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
