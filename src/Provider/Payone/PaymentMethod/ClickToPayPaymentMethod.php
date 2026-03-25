<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\Payone\PaymentHandler\ClickToPayPaymentHandler;
use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;

class ClickToPayPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = '019bc1f0a9c572fba4c34ab1e32bad6f';

    final public const TECHNICAL_NAME = 'payone_click_to_pay';

    final public const CONFIGURATION_PREFIX = 'clickToPay';

    public function __construct()
    {
        parent::__construct(
            ClickToPayPaymentHandler::class,
            true,
            'PAYONE Kreditkarte (Click to Pay)',
            null,
            'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
            [
                'de-DE' => [
                    'name'        => 'PAYONE Kreditkarte (Click to Pay)',
                    'description' => 'Zahlen Sie sicher mit Ihrer Kreditkarte über unseren PCI DSS zertifizierten Zahlungsprovider. Nach der Bestellung werden Sie ggf. auf eine Seite Ihrer Bank weitergeleitet, um die Zahlung zu autorisieren.',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE Credit Card (Click to Pay)',
                    'description' => 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
                ],
            ],
            100,
            '@Storefront/storefront/payone/click-to-pay/click-to-pay-form.html.twig',
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
