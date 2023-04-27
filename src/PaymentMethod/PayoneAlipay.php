<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler;

class PayoneAlipay extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Alipay';

    protected string $description = 'Pay save and secured with Alipay';

    protected string $paymentHandler = PayoneAlipayPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Alipay',
            'description' => 'Zahle sicher und geschützt mit Alipay',
        ],
        'en-GB' => [
            'name' => 'PAYONE Alipay',
            'description' => 'Pay save and secured with Alipay',
        ],
    ];

    protected int $position = 170;
}
