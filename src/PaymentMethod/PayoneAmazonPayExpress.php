<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;

class PayoneAmazonPayExpress extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_amazon_pay_express';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Amazon Pay Express';

    protected string $description = 'Pay save and secured with Amazon Pay';

    protected string $paymentHandler = PayoneAmazonPayExpressPaymentHandler::class;

    protected ?string $template = null;

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Amazon Pay Express',
            'description' => 'Zahle sicher und geschützt mit Amazon Pay',
        ],
        'en-GB' => [
            'name' => 'PAYONE Amazon Pay Express',
            'description' => 'Pay save and secured with Amazon Pay',
        ],
    ];

    protected int $position = 230;
}
