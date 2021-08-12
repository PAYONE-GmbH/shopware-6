<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;

class PayoneDebit extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS['PayoneDebit'];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone SEPA Lastschrift';

    /** @var string */
    protected $description = 'We\'ll automatically debit the amount from your bank account.';

    /** @var string */
    protected $paymentHandler = PayoneDebitPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/debit/debit-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone SEPA Lastschrift',
            'description' => 'Wir ziehen den Betrag bequem und automatisch von Ihrem Bankkonto ein.',
        ],
        'en-GB' => [
            'name'        => 'Payone SEPA Direct Debit',
            'description' => 'We\'ll automatically debit the amount from your bank account.',
        ],
    ];

    /** @var int */
    protected $position = 101;
}
