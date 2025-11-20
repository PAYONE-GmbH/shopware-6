<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\RequestParameter\Enricher;

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

        $requestData = $arguments->requestData->all();

        return [
            'request'                               => $requestActionEnum->value,
            'clearingtype'                          => PayoneClearingEnum::WALLET->value,
            'wallettype'                            => 'GGP',
            'add_paydata[paymentmethod_token_data]' => $requestData['google-pay-token'] ?? '',
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
