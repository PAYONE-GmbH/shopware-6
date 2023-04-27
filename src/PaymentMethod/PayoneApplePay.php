<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneApplePayPaymentHandler;

class PayoneApplePay extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Apple Pay';

    protected string $description = 'Apple Pay is a mobile payment system providing straightforward payment on Apple devices';

    protected string $paymentHandler = PayoneApplePayPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Apple Pay',
            'description' => 'Apple Pay ist ein mobiles Zahlungssystem, welches die bequeme Zahlung auf Endgeräten von Apple ermöglicht.',
        ],
        'en-GB' => [
            'name' => 'PAYONE Apple Pay',
            'description' => 'Apple Pay is a mobile payment system providing straightforward payment on Apple devices',
        ],
    ];

    protected int $position = 100;
}
