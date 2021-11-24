<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;

class PayoneOpenInvoice extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Open Invoice';

    /** @var string */
    protected $description = 'Open invoice payment.';

    /** @var string */
    protected $paymentHandler = PayoneOpenInvoicePaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/open-invoice/open-invoice.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone offener Rechnungskauf',
            'description' => 'Bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'Payone open invoice',
            'description' => 'Pay by invoice.',
        ],
    ];

    /** @var int */
    protected $position = 115;
}
