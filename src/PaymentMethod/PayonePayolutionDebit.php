<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;

class PayonePayolutionDebit extends AbstractPaymentMethod
{
    public const UUID = '700954775fad4a8f92463b3d629c8ad5';

    protected $id = self::UUID;
    protected $name = 'Payone Paysafe Pay Later Debit';
    protected $description = 'Pay by debit.';
    protected $paymentHandler = PayonePayolutionDebitPaymentHandler::class;
    protected $template = '@Storefront/payone/payolution/payolution-debit-form.html.twig';

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

    protected $position = 107;
}
