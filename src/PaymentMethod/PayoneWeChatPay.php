<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;

class PayoneWeChatPay extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'PAYONE WeChat Pay';

    protected string $description = 'Pay save and secured with WeChat Pay';

    protected string $paymentHandler = PayoneWeChatPayPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE WeChat Pay',
            'description' => 'Zahle sicher und geschützt mit WeChat Pay',
        ],
        'en-GB' => [
            'name' => 'PAYONE WeChat Pay',
            'description' => 'Pay save and secured with WeChat Pay',
        ],
    ];

    protected int $position = 180;
}
