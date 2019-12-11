<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;

class PayonePayolutionDebit extends AbstractPaymentMethod
{
    public const UUID = '700954775fad4a8f92463b3d629c8ad5';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Paysafe Pay Later Debit';

    /** @var string */
    protected $description = 'Pay by debit.';

    /** @var string */
    protected $paymentHandler = PayonePayolutionDebitPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/payone/payolution/payolution-debit-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Paysafe Pay Later Lastschrift',
            'description' => 'Sie zahlen per Lastschrift.',
        ],
        'en-GB' => [
            'name'        => 'Payone Paysafe Pay Later Debit',
            'description' => 'Pay by debit.',
        ],
    ];

    /** @var int */
    protected $position = 107;
}
