<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;

class PayonePayolutionInstallment extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Unzer Ratenkauf';

    protected string $description = 'Installment payment by Paysafe Pay Later.';

    protected string $paymentHandler = PayonePayolutionInstallmentPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/payolution/payolution-installment-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Unzer Ratenkauf',
            'description' => 'Bezahlen Sie einfach und bequem in monatlichen Raten.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Unzer Ratenkauf',
            'description' => 'Easily pay in monthly installments.',
        ],
    ];

    protected int $position = 104;
}
