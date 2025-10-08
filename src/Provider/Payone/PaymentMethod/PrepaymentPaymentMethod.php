<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\PrepaymentPaymentHandler;

class PrepaymentPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '267699739afd4cdd9663cac0bd269da6';

    final public const TECHNICAL_NAME = 'payone_prepayment';

    final public const CONFIGURATION_PREFIX = 'prepayment';

    public function __construct()
    {
        parent::__construct(
            PrepaymentPaymentHandler::class,
            true,
            'PAYONE Prepayment',
            null,
            'Pay by prepayment.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Vorkasse',
                    'description' => 'Sie zahlen per Vorkasse und erhalten die Ware nach Zahlungseingang.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Prepayment',
                    'description' => 'Pay in advance and receive your order after we received your payment.',
                ],
            ],
            120,
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
