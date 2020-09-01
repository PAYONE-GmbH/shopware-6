<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneCreditCard extends AbstractPaymentMethod
{
    public const UUID = '37f90a48d9194762977c9e6db36334e0';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Credit Card';

    /** @var string */
    protected $description = 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.';

    /** @var string */
    protected $paymentHandler = PayoneCreditCardPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/credit-card/credit-card-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Kreditkarte',
            'description' => 'Zahlen Sie sicher mit Ihrer Kreditkarte über unseren PCI DSS zertifizierten Zahlungsprovider. Nach der Bestellung werden Sie ggf. auf eine Seite Ihrer Bank weitergeleitet, um die Zahlung zu autorisieren.',
        ],
        'en-GB' => [
            'name'        => 'Payone Credit Card',
            'description' => 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
        ],
    ];

    /** @var int */
    protected $position = 100;
}
