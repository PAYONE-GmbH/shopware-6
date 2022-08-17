<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;

class PayoneRatepayInstallment extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'PAYONE Ratepay Installments';

    /** @var string */
    protected $description = 'Pay with Ratepay Installments';

    /** @var string */
    protected $paymentHandler = PayoneRatepayInstallmentPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/ratepay/ratepay-installment-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE Ratepay Ratenkauf',
            'description' => 'Zahle mit Ratepay Ratenkauf',
        ],
        'en-GB' => [
            'name'        => 'PAYONE Ratepay Installments',
            'description' => 'Pay with Ratepay Installments',
        ],
    ];

    /** @var int */
    protected $position = 132;
}
