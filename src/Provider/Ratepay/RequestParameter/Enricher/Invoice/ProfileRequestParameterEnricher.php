<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Invoice;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum as GeneralRequestActionEnum;
use PayonePayment\Provider\Ratepay\RequestParameter\ProfileRequestDto;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<ProfileRequestDto>
 */
readonly class ProfileRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function enrich(AbstractRequestDto $arguments): array
    {
        return [
            'request'              => GeneralRequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'  => 'profile',
            'add_paydata[shop_id]' => $arguments->shopId,
            'currency'             => $arguments->currency,
            'clearingtype'         => PayoneClearingEnum::FINANCING->value,
            'financingtype'        => PayoneFinancingEnum::RPV->value,
        ];
    }
}
