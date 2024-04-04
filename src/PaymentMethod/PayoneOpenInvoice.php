<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;

class PayoneOpenInvoice extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_open_invoice';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Invoice';

    protected string $description = 'Open invoice payment.';

    protected string $paymentHandler = PayoneOpenInvoicePaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Rechnungskauf',
            'description' => 'Bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Invoice',
            'description' => 'Pay by invoice.',
        ],
    ];

    protected int $position = 115;
}
