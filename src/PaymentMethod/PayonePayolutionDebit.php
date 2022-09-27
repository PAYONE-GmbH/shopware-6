<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;

class PayonePayolutionDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Unzer Lastschrift';

    protected string $description = 'SEPA Direct Debit by Paysafe Pay Later.';

    protected string $paymentHandler = PayonePayolutionDebitPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/payolution/payolution-debit-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Unzer Lastschrift',
            'description' => 'Gesicherte Lastschrift von Paysafe Pay Later.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Unzer Lastschrift',
            'description' => 'SEPA Direct Debit by Paysafe Pay Later.',
        ],
    ];

    protected int $position = 107;
}
