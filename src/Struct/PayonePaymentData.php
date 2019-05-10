<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PayonePaymentData extends Struct
{
    /** @var array */
    protected $cardRequest = [];

    /** @var null|string */
    protected $language;

    public function getCardRequest(): array
    {
        return $this->cardRequest;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
