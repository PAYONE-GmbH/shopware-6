<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;

class PayoneSecureInvoice extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_secure_invoice';

    final public const BUSINESSRELATION_B2B = 'b2b';
    final public const BUSINESSRELATION_B2C = 'b2c';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Secure Invoice';

    protected string $description = 'Secure invoice payment.';

    protected string $paymentHandler = PayoneSecureInvoicePaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/secure-invoice/secure-invoice.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Gesicherter Rechnungskauf',
            'description' => 'Abgesichert bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Secure Invoice',
            'description' => 'Secure pay by invoice. After reception of goods.',
        ],
    ];

    protected int $position = 114;
}
