<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;

class PayonePrepayment extends AbstractPaymentMethod
{
    public const UUID = '267699739afd4cdd9663cac0bd269da6';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Prepayment';

    /** @var string */
    protected $description = 'Pay by prepayment.';

    /** @var string */
    protected $paymentHandler = PayonePrepaymentPaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Vorkasse',
            'description' => 'Sie zahlen per Vorkasse und erhalten die Ware nach Zahlungseingang.',
        ],
        'en-GB' => [
            'name'        => 'Payone Prepayment',
            'description' => 'Pay in advance and receive your order after we received your payment.',
        ],
    ];

    /** @var int */
    protected $position = 120;
}
