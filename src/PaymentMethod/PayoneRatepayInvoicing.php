<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;

class PayoneRatepayInvoicing extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'PAYONE Ratepay Open Invoice';

    /** @var string */
    protected $description = 'Pay with Ratepay Open Invoice';

    /** @var string */
    protected $paymentHandler = PayoneRatepayInvoicingPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/ratepay/ratepay-invoicing-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'PAYONE Ratepay Rechnungskauf',
            'description' => 'Zahle mit dem Ratepay Rechnungskauf',
        ],
        'en-GB' => [
            'name'        => 'PAYONE Ratepay Open Invoice',
            'description' => 'Pay with Ratepay Open Invoice',
        ],
    ];

    /** @var int */
    protected $position = 130;
}
