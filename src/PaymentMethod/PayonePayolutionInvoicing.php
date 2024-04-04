<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;

class PayonePayolutionInvoicing extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_unzer_invoice';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Unzer Rechnungskauf';

    protected string $description = 'Invoice payment by Paysafe Pay Later.';

    protected string $paymentHandler = PayonePayolutionInvoicingPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/payolution/payolution-invoicing-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Unzer Rechnungskauf',
            'description' => 'Sie zahlen entspannt nach Erhalt der Ware auf Rechnung.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Unzer Rechnungskauf',
            'description' => 'Pay the invoice after receiving the goods.',
        ],
    ];

    protected int $position = 105;
}
