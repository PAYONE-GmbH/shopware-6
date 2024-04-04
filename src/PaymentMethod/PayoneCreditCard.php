<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneCreditCard extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_creditcard';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Credit Card';

    protected string $description = 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.';

    protected string $paymentHandler = PayoneCreditCardPaymentHandler::class;

    protected ?string $template = '@Storefront/storefront/payone/credit-card/credit-card-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Kreditkarte',
            'description' => 'Zahlen Sie sicher mit Ihrer Kreditkarte über unseren PCI DSS zertifizierten Zahlungsprovider. Nach der Bestellung werden Sie ggf. auf eine Seite Ihrer Bank weitergeleitet, um die Zahlung zu autorisieren.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Credit Card',
            'description' => 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
        ],
    ];

    protected int $position = 100;
}
