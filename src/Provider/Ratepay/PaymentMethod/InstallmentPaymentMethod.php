<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;

class InstallmentPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '0af0f201fd164ca9ae72313c70201d18';

    final public const TECHNICAL_NAME = 'payone_ratepay_installment';

    final public const CONFIGURATION_PREFIX = 'ratepayInstallment';

    public function __construct()
    {
        parent::__construct(
            InstallmentPaymentHandler::class,
            true,
            'PAYONE Ratepay Installments',
            null,
            'Pay with Ratepay Installments',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Ratepay Ratenkauf',
                    'description' => 'Zahle mit Ratepay Ratenkauf',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Ratepay Installments',
                    'description' => 'Pay with Ratepay Installments',
                ],
            ],
            132,
            '@Storefront/storefront/payone/ratepay/ratepay-installment-form.html.twig',
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
