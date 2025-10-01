<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler;

class SecuredInstallmentPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '9c4d04f6ad4b4a2787e3812c56b6153b';

    final public const TECHNICAL_NAME = 'payone_secured_installment';

    final public const CONFIGURATION_PREFIX = 'securedInstallment';

    public function __construct()
    {
        parent::__construct(
            SecuredInstallmentPaymentHandler::class,
            false,
            'PAYONE Secured Installment',
            null,
            'Pay with secured installment',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Gesicherter Ratenkauf',
                    'description' => 'Zahle mit dem gesicherten Ratenkauf',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Secured Installment',
                    'description' => 'Pay with secured installment',
                ],
            ],
            200,
            '@Storefront/storefront/payone/secured-installment/secured-installment.html.twig',
        );
    }

    public static function getId(): string
    {
        return self::UUID;
    }

    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
