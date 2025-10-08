<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\CreditCard;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Payone\Enum\CreditCardRequestParamEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\CardRepositoryService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class PreauthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private CardRepositoryService $cardRepository,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        if ($arguments->action !== RequestActionEnum::PREAUTHORIZE->value) {
            return [];
        }

        $cardHolder         = $arguments->requestData->get(CreditCardRequestParamEnum::CARD_HOLDER->value);
        $cardType           = $arguments->requestData->get(CreditCardRequestParamEnum::CARD_TYPE->value);
        $pseudoCardPan      = $arguments->requestData->get(CreditCardRequestParamEnum::PSEUDO_CARD_PAN->value);
        $savedPseudoCardPan = $arguments->requestData->get(CreditCardRequestParamEnum::SAVED_PSEUDO_CARD_PAN->value);

        $salesChannelContext = $arguments->salesChannelContext;

        if (!empty($savedPseudoCardPan) && !empty($salesChannelContext->getCustomerId())) {
            $savedCard = $this->cardRepository->getExistingCard(
                $salesChannelContext->getCustomerId(),
                $savedPseudoCardPan,
                $salesChannelContext->getContext(),
            );
            $pseudoCardPan = $savedCard?->getPseudoCardPan() ?: '';
            $cardHolder    = $savedCard?->getCardHolder() ?: $cardHolder;
        }

        return [
            'request'       => $arguments->action,
            'clearingtype'  => PayoneClearingEnum::CREDIT_CARD->value,
            'pseudocardpan' => $pseudoCardPan,
            'cardtype'      => $cardType,
            'cardholder'    => $cardHolder,
        ];
    }
}
