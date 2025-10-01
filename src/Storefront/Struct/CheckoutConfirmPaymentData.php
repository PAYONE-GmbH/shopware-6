<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\Struct;

class CheckoutConfirmPaymentData extends Struct
{
    final public const EXTENSION_NAME = 'payone';

    protected array $cardRequest = [];

    protected string|null $language = null;

    protected EntitySearchResult|null $savedCards = null;

    protected string|null $template = null;

    protected string|null $workOrderId = null;

    protected string|null $cartHash = '';

    protected bool $showExitExpressCheckoutLink = false;

    protected bool $preventAddressEdit = false;

    public function getCardRequest(): array
    {
        return $this->cardRequest;
    }

    public function getLanguage(): string|null
    {
        return $this->language;
    }

    public function getSavedCards(): EntitySearchResult|null
    {
        return $this->savedCards;
    }

    public function getTemplate(): string|null
    {
        return $this->template;
    }

    public function getWorkOrderId(): string|null
    {
        return $this->workOrderId;
    }

    public function getCartHash(): string|null
    {
        return $this->cartHash;
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
