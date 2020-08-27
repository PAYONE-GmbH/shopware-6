<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;

class PayoneSecureInvoice extends AbstractPaymentMethod
{
    public const UUID                 = '0b532088e2da3092f9f7054ec4009d18';
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
    protected $template = '@Storefront/storefront/payone/secureinovice/secure-invoice-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone sicherer Rechnungskauf',
            'description' => 'Abgesichert bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'Payone secure invoice',
            'description' => 'Pay by invoice. After reception of goods.',
        ],
    ];
}
