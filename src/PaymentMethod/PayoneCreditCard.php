<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;

class PayoneCreditCard implements PaymentMethodInterface
{
    public const UUID = '37f90a48d9194762977c9e6db36334e0';

    /** @var string */
    private $name = 'Payone Credit Card';

    /** @var string */
    private $description = 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.';

    /** @var string */
    private $paymentHandler = PayoneCreditCardPaymentHandler::class;

    /** @var null|string */
    private $template = '@Storefront/storefront/payone/credit-card/credit-card-form.html.twig';

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone Kreditkarte',
            'description' => 'Zahlen Sie sicher mit Ihrer Kreditkarte über unseren PCI DSS zertifizierten Zahlungsprovider. Nach der Bestellung werden Sie ggf. auf eine Seite Ihrer Bank weitergeleitet, um die Zahlung zu autorisieren.',
        ],
        'en-GB' => [
            'name'        => 'Payone Credit Card',
            'description' => 'Use your credit card to safely pay through our PCI DSS certified payment provider. After your order, you may be redirected to your bank to authorize the payment.',
        ],
    ];

    /** @var int */
    private $position = 100;

    public function getId(): string
    {
        return self::UUID;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
