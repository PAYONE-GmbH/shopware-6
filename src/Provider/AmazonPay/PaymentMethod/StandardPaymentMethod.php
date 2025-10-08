<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\AmazonPay\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'ae2b29f0b99d4ba9852063d84d198180';

    final public const TECHNICAL_NAME = 'payone_amazon_pay';

    final public const CONFIGURATION_PREFIX = 'amazonPay';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            true,
            'PAYONE Amazon Pay',
            null,
            'Pay save and secured with Amazon Pay',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Amazon Pay',
                    'description' => 'Zahle sicher und geschützt mit Amazon Pay',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Amazon Pay',
                    'description' => 'Pay save and secured with Amazon Pay',
                ],
            ],
            220,
            '@PayonePayment/storefront/payone/amazon-pay/amazon-pay-form.html.twig',
        );
    }

    #[\Override]
    public static function getId(): string
    {
        return self::UUID;
    }

    #[\Override]
    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    #[\Override]
    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
