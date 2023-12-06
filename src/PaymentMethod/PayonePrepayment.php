<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;

class PayonePrepayment extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Prepayment';

    protected string $description = 'Pay by prepayment.';

    protected string $paymentHandler = PayonePrepaymentPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Vorkasse',
            'description' => 'Sie zahlen per Vorkasse und erhalten die Ware nach Zahlungseingang.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Prepayment',
            'description' => 'Pay in advance and receive your order after we received your payment.',
        ],
    ];

    protected int $position = 120;
}
