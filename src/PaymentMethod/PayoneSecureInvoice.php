<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;

class PayoneSecureInvoice extends AbstractPaymentMethod
{
    public const UUID                 = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];
    public const BUSINESSRELATION_B2B = 'b2b';
    public const BUSINESSRELATION_B2C = 'b2c';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Secure Invoice';

    /** @var string */
    protected $description = 'Secure invoice payment.';

    /** @var string */
    protected $paymentHandler = PayoneSecureInvoicePaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/secure-invoice/secure-invoice.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone gesicherter Rechnungskauf',
            'description' => 'Abgesichert bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'Payone secure invoice',
            'description' => 'Secure pay by invoice. After reception of goods.',
        ],
    ];

    /** @var int */
    protected $position = 114;
}
