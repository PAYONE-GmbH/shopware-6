<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;

class PayoneSecuredInvoice extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Secured Invoice';

    protected string $description = 'Pay with secured open invoice';

    protected string $paymentHandler = PayoneSecuredInvoicePaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/secured-invoice/secured-invoice.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Gesicherter Rechnungskauf',
            'description' => 'Zahle mit dem gesicherten Rechnungskauf',
        ],
        'en-GB' => [
            'name' => 'PAYONE Secured Invoice',
            'description' => 'Pay with secured open invoice',
        ],
    ];

    protected int $position = 190;
}
