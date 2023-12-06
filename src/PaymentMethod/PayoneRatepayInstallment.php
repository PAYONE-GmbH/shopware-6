<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;

class PayoneRatepayInstallment extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Ratepay Installments';

    protected string $description = 'Pay with Ratepay Installments';

    protected string $paymentHandler = PayoneRatepayInstallmentPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/ratepay/ratepay-installment-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Ratepay Ratenkauf',
            'description' => 'Zahle mit Ratepay Ratenkauf',
        ],
        'en-GB' => [
            'name' => 'PAYONE Ratepay Installments',
            'description' => 'Pay with Ratepay Installments',
        ],
    ];

    protected int $position = 132;
}
