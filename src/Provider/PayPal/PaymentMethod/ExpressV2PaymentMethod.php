<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodAwareInterface;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodTrait;
use PayonePayment\Provider\PayPal\PaymentHandler\ExpressV2PaymentHandler;

class ExpressV2PaymentMethod extends AbstractPaymentMethod implements ExpressCheckoutPaymentMethodAwareInterface
{
    use ExpressCheckoutPaymentMethodTrait;

    final public const UUID = '57fa8d8c9d3b4e488f5267f624841531';

    final public const TECHNICAL_NAME = 'payone_paypal_v2_express';

    final public const CONFIGURATION_PREFIX = 'paypalV2Express';

    public function __construct()
    {
        parent::__construct(
            ExpressV2PaymentHandler::class,
            false,
            'PAYONE Paypal Express',
            'PAYONE Paypal Express v2',
            'Pay easily and secure with PayPal Express.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE PayPal Express',
                    'description' => 'Zahlen Sie sicher und bequem mit PayPal Express.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE PayPal Express',
                    'description' => 'Pay easily and secure with PayPal Express.',
                ],
            ],
            103,
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
