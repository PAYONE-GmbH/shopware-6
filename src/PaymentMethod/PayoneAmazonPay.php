<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentHandler\PayoneAmazonPayPaymentHandler;

class PayoneAmazonPay extends AbstractPaymentMethod
{
    final public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    final public const TECHNICAL_NAME = 'payone_amazon_pay';

    protected string $id = self::UUID;

    protected string $name = 'PAYONE Amazon Pay';

    protected string $description = 'Pay save and secured with Amazon Pay';

    protected string $paymentHandler = PayoneAmazonPayPaymentHandler::class;

    protected ?string $template = '@PayonePayment/storefront/payone/amazon-pay/amazon-pay-form.html.twig';

    protected array $translations = [
        'de-DE' => [
            'name' => 'PAYONE Amazon Pay',
            'description' => 'Zahle sicher und geschützt mit Amazon Pay',
        ],
        'en-GB' => [
            'name' => 'PAYONE Amazon Pay',
            'description' => 'Pay save and secured with Amazon Pay',
        ],
    ];

    protected int $position = 220;
}
