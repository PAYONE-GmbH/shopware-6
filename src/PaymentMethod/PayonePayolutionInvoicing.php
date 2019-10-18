<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;

class PayonePayolutionInvoicing implements PaymentMethodInterface
{
    public const UUID = '0407fd0a5c4b4d2bafc88379efe8cf8d';

    /** @var string */
    private $name = 'Payone Invoice';

    /** @var string */
    private $description = 'Pay the invoice after receiving the goods.';

    /** @var string */
    private $paymentHandler = PayonePayolutionInvoicingPaymentHandler::class;

    /** @var null|string */
    private $template = '@Storefront/payone/payolution/payolution-invoicing-form.html.twig';

    /** @var array */
    private $translations = [
        'de-DE' => [
            'name'        => 'Payone Rechnung',
            'description' => 'Sie zahlen entspannt nach Erhalt der Ware auf Rechnung.',
        ],
        'en-GB' => [
            'name'        => 'Payone Invoice',
            'description' => 'Pay the invoice after receiving the goods.',
        ],
    ];

    /** @var int */
    private $position = 105;

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
