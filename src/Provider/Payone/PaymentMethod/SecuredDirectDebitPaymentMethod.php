<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredDirectDebitPaymentHandler;

class SecuredDirectDebitPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '72c4c88b918441848e20081de67a16c4';

    final public const TECHNICAL_NAME = 'payone_secured_direct_debit';

    final public const CONFIGURATION_PREFIX = 'securedDirectDebit';

    public function __construct()
    {
        parent::__construct(
            SecuredDirectDebitPaymentHandler::class,
            false,
            'PAYONE Secured Direct Debit',
            null,
            'Pay with secured direct debit',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Gesicherte Lastschrift',
                    'description' => 'Zahle mit der gesicherten Lastschrift',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Secured Direct Debit',
                    'description' => 'Pay with secured direct debit',
                ],
            ],
            210,
            '@Storefront/storefront/payone/secured-direct-debit/secured-direct-debit.html.twig',
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
