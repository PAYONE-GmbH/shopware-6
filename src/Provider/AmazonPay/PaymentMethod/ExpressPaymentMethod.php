<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodAwareInterface;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodTrait;
use PayonePayment\Provider\AmazonPay\PaymentHandler\ExpressPaymentHandler;

class ExpressPaymentMethod extends AbstractPaymentMethod implements ExpressCheckoutPaymentMethodAwareInterface
{
    use ExpressCheckoutPaymentMethodTrait;

    final public const UUID = 'd310a86cdaf14dd6b69bcf2b98f23268';

    final public const TECHNICAL_NAME = 'payone_amazon_pay_express';

    final public const CONFIGURATION_PREFIX = 'amazonPayExpress';

    public function __construct()
    {
        parent::__construct(
            ExpressPaymentHandler::class,
            false,
            'PAYONE Amazon Pay Express',
            null,
            'Pay save and secured with Amazon Pay',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Amazon Pay Express',
                    'description' => 'Zahle sicher und geschützt mit Amazon Pay',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Amazon Pay Express',
                    'description' => 'Pay save and secured with Amazon Pay',
                ],
            ],
            230,
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
