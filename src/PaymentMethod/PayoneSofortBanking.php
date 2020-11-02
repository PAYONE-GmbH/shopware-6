<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;

/**
 * TODO: only valid in DE, AT, CH, NL. Use ruleEngine to enforce this during the checkout
 */
class PayoneSofortBanking extends AbstractPaymentMethod
{
    public const UUID = '9022c4733d14411e84a78707088487aa';

    /** @var string */
    protected $id = self::UUID;

    /** @var string */
    protected $name = 'Payone Sofort';

    /** @var string */
    protected $description = 'Wire the amount instantly with your online banking credentials.';

    /** @var string */
    protected $paymentHandler = PayoneSofortBankingPaymentHandler::class;

    /** @var null|string */
    protected $template;

    /** @var array */
    protected $translations = [
        'de-DE' => [
            'name'        => 'Payone Sofort',
            'description' => 'Überweisen Sie schnell und sicher mit Ihren Online Banking Zugangsdaten.',
        ],
        'en-GB' => [
            'name'        => 'Payone Sofort',
            'description' => 'Wire the amount instantly with your online banking credentials.',
        ],
    ];

    /** @var int */
    protected $position = 106;
}
