<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

class AbstractPayoneKlarna extends AbstractPaymentMethod
{
    protected ?string $template = '@Storefront/storefront/payone/klarna/klarna.html.twig';
}
