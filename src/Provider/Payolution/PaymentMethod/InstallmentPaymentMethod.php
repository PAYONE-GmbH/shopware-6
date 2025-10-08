<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payolution\PaymentHandler\InstallmentPaymentHandler;

class InstallmentPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '569b46970ad2458ca8f17f1ebb754137';

    final public const TECHNICAL_NAME = 'payone_unzer_installment';

    final public const CONFIGURATION_PREFIX = 'unzerInstallment';

    public function __construct()
    {
        parent::__construct(
            InstallmentPaymentHandler::class,
            true,
            'PAYONE Unzer Ratenkauf',
            null,
            'Installment payment by Paysafe Pay Later.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Unzer Ratenkauf',
                    'description' => 'Bezahlen Sie einfach und bequem in monatlichen Raten.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Unzer Ratenkauf',
                    'description' => 'Easily pay in monthly installments.',
                ],
            ],
            104,
            '@Storefront/storefront/payone/payolution/payolution-installment-form.html.twig',
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
