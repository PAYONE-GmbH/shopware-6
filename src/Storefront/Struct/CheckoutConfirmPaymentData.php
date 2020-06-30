<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\Struct;

class CheckoutConfirmPaymentData extends Struct
{
    public const EXTENSION_NAME = 'payone';

    /** @var array */
    protected $cardRequest = [];

    /** @var null|string */
    protected $language;

    /** @var null|EntitySearchResult */
    protected $savedCards;

    /** @var null|string */
    protected $template;

    /** @var null|string */
    protected $workOrderId;

    /** @var null|string */
    protected $cartHash = '';

    public function getCardRequest(): array
    {
        return $this->cardRequest;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getSavedCards(): ?EntitySearchResult
    {
        return $this->savedCards;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getWorkOrderId(): ?string
    {
        return $this->workOrderId;
    }

    public function getCartHash(): ?string
    {
        return $this->cartHash;
    }
}
