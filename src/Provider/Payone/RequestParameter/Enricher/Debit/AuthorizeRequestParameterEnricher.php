<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\Debit;

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
            'request'           => $requestActionEnum->value,
            'clearingtype'      => PayoneClearingEnum::DEBIT->value,
            'iban'              => $arguments->requestData->get('iban', ''),
            'bic'               => $arguments->requestData->get('bic', ''),
            'bankaccountholder' => $arguments->requestData->get('accountOwner', ''),
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
