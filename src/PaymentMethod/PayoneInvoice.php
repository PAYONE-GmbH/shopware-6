<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneInvoicePaymentHandler;

class PayoneInvoice extends AbstractPaymentMethod
{
    public const UUID = '542c46fadb9d4d53a0994c5ad23ced3d';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Invoice';

    /** @var string */
    protected $description = 'Invoice payment.';

    /** @var string */
    protected $paymentHandler = PayoneInvoicePaymentHandler::class;

    /** @var null|string */
    protected $template = null;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE Rechnungskauf',
            'description' => 'Bezahlen per Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'PAYONE Invoice',
            'description' => 'Pay by invoice.',
        ],
    ];

    /** @var int */
    protected $position = 116;
}
