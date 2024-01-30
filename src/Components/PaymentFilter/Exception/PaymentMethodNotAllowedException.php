<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter\Exception;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;

class PaymentMethodNotAllowedException extends \Exception
{
    public function __construct(
        string $message,
        private readonly ?PaymentMethodCollection $disallowedPaymentMethodCollection = null
    ) {
        parent::__construct($message);
    }

    public function getDisallowedPaymentMethodCollection(): ?PaymentMethodCollection
    {
        return $this->disallowedPaymentMethodCollection;
    }
}
