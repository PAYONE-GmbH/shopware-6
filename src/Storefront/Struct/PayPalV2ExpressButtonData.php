<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PayPalV2ExpressButtonData extends Struct
{
    final public const EXTENSION_NAME = 'payonePayPalV2ExpressButton';

    protected bool $sandbox;

    protected string $clientId;

    protected string $merchantId;

    protected string $currency;

    protected string $locale;

    protected bool $showPayLaterButton;

    protected string $createCheckoutSessionUrl;

    protected string $onApproveRedirectUrl;

    protected string $onCancelRedirectUrl;

    protected string $onErrorRedirectUrl;

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function isShowPayLaterButton(): bool
    {
        return $this->showPayLaterButton;
    }

    public function getCreateCheckoutSessionUrl(): string
    {
        return $this->createCheckoutSessionUrl;
    }

    public function getOnApproveRedirectUrl(): string
    {
        return $this->onApproveRedirectUrl;
    }

    public function getOnCancelRedirectUrl(): string
    {
        return $this->onCancelRedirectUrl;
    }

    public function getOnErrorRedirectUrl(): string
    {
        return $this->onErrorRedirectUrl;
    }
}
