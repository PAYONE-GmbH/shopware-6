<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

class AbstractPayoneKlarna extends AbstractPaymentMethod
{
    /** @var string */
    protected $template = '@Storefront/storefront/payone/klarna/klarna.html.twig';
}
