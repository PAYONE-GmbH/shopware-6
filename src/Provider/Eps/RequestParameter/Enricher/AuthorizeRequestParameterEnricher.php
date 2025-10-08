<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Eps\RequestParameter\Enricher;

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

        $dataBag = $arguments->requestData;

        return [
            'request'                => $requestActionEnum->value,
            'clearingtype'           => PayoneClearingEnum::ONLINE_BANK_TRANSFER->value,
            'onlinebanktransfertype' => 'EPS',
            'bankcountry'            => 'AT',
            'bankgrouptype'          => $dataBag->get('epsBankGroup'),
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
