<?php

declare(strict_types=1);

namespace PayonePayment\Provider\SofortBanking\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        return [
            'request'                => $requestActionEnum->value,
            'clearingtype'           => PayoneClearingEnum::ONLINE_BANK_TRANSFER->value,
            'onlinebanktransfertype' => 'PNT',

            // TODO: possible values DE, AT, CH, NL (this has not been implemented before)
            'bankcountry'            => 'DE',
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
