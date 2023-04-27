<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;

class PayoneRatepayInvoicing extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Ratepay Open Invoice';

    protected string $description = 'Pay with Ratepay Open Invoice';

    protected string $paymentHandler = PayoneRatepayInvoicingPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/ratepay/ratepay-invoicing-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Ratepay Rechnungskauf',
            'description' => 'Zahle mit dem Ratepay Rechnungskauf',
        ],
        'en-GB' => [
            'name' => 'PAYONE Ratepay Open Invoice',
            'description' => 'Pay with Ratepay Open Invoice',
        ],
    ];

    protected int $position = 130;
}
