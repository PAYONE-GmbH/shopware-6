<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\Struct;

class CheckoutConfirmPaymentData extends Struct
{
    final public const EXTENSION_NAME = 'payone';

    protected array $cardRequest = [];

    protected ?string $language = null;

    protected ?EntitySearchResult $savedCards = null;

    protected ?string $template = null;

    protected ?string $workOrderId = null;

    protected ?string $cartHash = '';

    protected ?EntitySearchResult $savedMandates = null;

    protected bool $showExitExpressCheckoutLink = false;

    protected bool $preventAddressEdit = false;

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

    public function getSavedMandates(): ?EntitySearchResult
    {
        return $this->savedMandates;
    }

    public function isshowExitExpressCheckoutLink(): bool
    {
        return $this->showExitExpressCheckoutLink;
    }

    public function isPreventAddressEdit(): bool
    {
        return $this->preventAddressEdit;
    }
}
