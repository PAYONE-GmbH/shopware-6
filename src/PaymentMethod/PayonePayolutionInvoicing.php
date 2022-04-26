<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;

class PayonePayolutionInvoicing extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Unzer Rechnungskauf';

    /** @var string */
    protected $description = 'Invoice payment by Paysafe Pay Later.';

    /** @var string */
    protected $paymentHandler = PayonePayolutionInvoicingPaymentHandler::class;

    /** @var null|string */
    protected $template = '@Storefront/storefront/payone/payolution/payolution-invoicing-form.html.twig';

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Unzer Rechnungskauf',
            'description' => 'Sie zahlen entspannt nach Erhalt der Ware auf Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'Unzer Rechnungskauf',
            'description' => 'Pay the invoice after receiving the goods.',
        ],
    ];

    /** @var int */
    protected $position = 105;
}
