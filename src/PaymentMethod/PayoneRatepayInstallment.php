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
    protected $name = 'Payone Ratepay Installments';

    /** @var string */
    protected $description = 'ToDo';

    /** @var string */
    protected $paymentHandler = PayoneRatepayInstallmentPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Ratepay Ratenkauf',
            'description' => 'ToDo',
        ],
        'en-GB' => [
            'name'        => 'Payone Ratepay Installments',
            'description' => 'ToDo',
        ],
    ];

    /** @var int */
    protected $position = 102; // ToDo
}
