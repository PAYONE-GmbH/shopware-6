<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;

class PayoneSecuredInstallment extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Secured Installment';

    protected string $description = 'Pay with secured installment';

    protected string $paymentHandler = PayoneSecuredInstallmentPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/secured-installment/secured-installment.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Gesicherter Ratenkauf',
            'description' => 'Zahle mit dem gesicherten Ratenkauf',
        ],
        'en-GB' => [
            'name' => 'PAYONE Secured Installment',
            'description' => 'Pay with secured installment',
        ],
    ];

    protected int $position = 200;
}
