<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;

class PayonePaypalExpress implements PaymentMethodInterface
{
    public const UUID = '5ddf648859a84396a98c97a1a92c107f';

    /** @var string */
    private $name = 'Payone Paypal Express';

    /** @var string */
    private $description = 'Pay easily and secure with PayPal Express.';

    /** @var string */
    private $paymentHandler = PayonePaypalExpressPaymentHandler::class;

    /** @var null|string */
    private $template;

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone PayPal Express',
            'description' => 'Zahlen Sie sicher und bequem mit PayPal Express.',
        ],
        'en-GB' => [
            'name'        => 'Payone PayPal Express',
            'description' => 'Pay easily and secure with PayPal Express.',
        ],
    ];

    /** @var int */
    private $position = 103;

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
