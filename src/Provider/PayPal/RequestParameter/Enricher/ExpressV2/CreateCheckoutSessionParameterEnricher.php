<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\RequestParameter\Enricher\ExpressV2;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class CreateCheckoutSessionParameterEnricher implements RequestParameterEnricherInterface
{
    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        return [
            'add_paydata[action]' => 'setexpresscheckout',
            'clearingtype'        => PayoneClearingEnum::WALLET->value,
            'wallettype'          => 'PAL',
        ];
    }
}
