<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;

class PayoneRatepayDebit extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Ratepay Direct Debit';

    protected string $description = 'Pay with Ratepay Direct Debit';

    protected string $paymentHandler = PayoneRatepayDebitPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/ratepay/ratepay-debit-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Ratepay Lastschrift',
            'description' => 'Zahle mit Ratepay Lastschrift',
        ],
        'en-GB' => [
            'name' => 'PAYONE Ratepay Direct Debit',
            'description' => 'Pay with Ratepay Direct Debit',
        ],
    ];

    protected int $position = 131;
}
