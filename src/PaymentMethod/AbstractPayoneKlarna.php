<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;

class AbstractPayoneKlarna extends AbstractPaymentMethod
{
    /** @var string */
    protected $template = '@Storefront/storefront/payone/klarna/klarna.html.twig';
}
