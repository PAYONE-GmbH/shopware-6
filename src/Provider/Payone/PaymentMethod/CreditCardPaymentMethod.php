<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;

class CreditCardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '37f90a48d9194762977c9e6db36334e0';

    final public const TECHNICAL_NAME = 'payone_creditcard';

    final public const CONFIGURATION_PREFIX = 'creditCard';

    public function __construct()
    {
        parent::__construct(
            CreditCardPaymentHandler::class,
            true,
            'PAYONE Credit Card',
            null,
            'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Kreditkarte',
                    'description' => 'Zahlen Sie sicher mit Ihrer Kreditkarte über unseren PCI DSS zertifizierten Zahlungsprovider. Nach der Bestellung werden Sie ggf. auf eine Seite Ihrer Bank weitergeleitet, um die Zahlung zu autorisieren.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Credit Card',
                    'description' => 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
                ],
            ],
            100,
            '@Storefront/storefront/payone/credit-card/credit-card-form.html.twig',
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
