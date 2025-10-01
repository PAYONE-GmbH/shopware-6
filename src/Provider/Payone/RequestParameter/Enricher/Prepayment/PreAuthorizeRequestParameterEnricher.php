<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\Prepayment;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class PreAuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function enrich(AbstractRequestDto $arguments): array
    {
        return [
            'request'      => RequestActionEnum::PREAUTHORIZE->value,
            'clearingtype' => PayoneClearingEnum::PREPAYMENT->value,
        ];
    }
}
