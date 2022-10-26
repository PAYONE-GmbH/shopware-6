<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler;

class PayoneKlarnaInstallment extends AbstractPayoneKlarna
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Klarna Ratenkauf'; // do not replace this by an english wording. (this is the product name)

    protected string $description = 'Pay with Klarna installments.';

    protected string $paymentHandler = PayoneKlarnaInstallmentPaymentHandler::class;

    protected array $translations = [
        'de-DE' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Zahle mit dem Klarna Ratenkauf.',
        ],
        'en-GB' => [
            // do not add de_DE translation for the name. (this::$name is the product name)
            'description' => 'Pay with Klarna installments.',
        ],
    ];

    protected int $position = 150;
}
