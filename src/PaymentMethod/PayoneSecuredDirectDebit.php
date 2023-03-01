<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;

class PayoneSecuredDirectDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Secured Direct Debit';

    protected string $description = 'Pay with secured direct debit';

    protected string $paymentHandler = PayoneSecuredDirectDebitPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/secured-direct-debit/secured-direct-debit.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Gesicherte Lastschrift',
            'description' => 'Zahle mit der gesicherten Lastschrift',
        ],
        'en-GB' => [
            'name' => 'PAYONE Secured Direct Debit',
            'description' => 'Pay with secured direct debit',
        ],
    ];

    protected int $position = 210;
}
