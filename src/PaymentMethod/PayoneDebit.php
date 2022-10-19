<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;

class PayoneDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Lastschrift';

    protected string $description = 'We\'ll automatically debit the amount from your bank account.';

    protected string $paymentHandler = PayoneDebitPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/debit/debit-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Lastschrift',
            'description' => 'Wir ziehen den Betrag bequem und automatisch von Ihrem Bankkonto ein.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Direct Debit',
            'description' => 'We\'ll automatically debit the amount from your bank account.',
        ],
    ];

    protected int $position = 101;
}
