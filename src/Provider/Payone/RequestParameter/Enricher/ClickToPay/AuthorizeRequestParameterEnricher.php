<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\ClickToPay;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Payone\Enum\ClickToPayRequestParamEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\CardRepositoryService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    private const CARD_TYPE_MAP = [
        'V' => ['visa'],
        'M' => ['mastercard'],
        'O' => ['maestro'],
        'D' => ['diners', 'discover'],
        'P' => ['china', 'union'],
        'A' => ['amex', 'american'],
        'J' => ['jcb'],
    ];

    public function __construct(
        private CardRepositoryService $cardRepository,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($arguments->action !== $requestActionEnum->value) {
            return [];
        }

        $cardHolder          = $arguments->requestData->getString(ClickToPayRequestParamEnum::CARD_HOLDER->value);
        $cardType            = $arguments->requestData->getString(ClickToPayRequestParamEnum::CARD_TYPE->value);
        $pseudoCardPan       = $arguments->requestData->getString(ClickToPayRequestParamEnum::PSEUDO_CARD_PAN->value);
        $expireDate          = $arguments->requestData->getString(ClickToPayRequestParamEnum::CARD_EXPIRE_DATE->value);
        $savedPseudoCardPan  = $arguments->requestData->getString(ClickToPayRequestParamEnum::SAVED_PSEUDO_CARD_PAN->value);
        $cardInputMode       = $arguments->requestData->getString(ClickToPayRequestParamEnum::CARD_INPUT_MODE->value);
        $paymentCheckoutData = $arguments->requestData->getString(ClickToPayRequestParamEnum::PAYMENT_CHECKOUT_DATA->value);

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

        if ($cardInputMode === 'clickToPay' || $cardInputMode === 'register') {
            return [
                'request'                           => $requestActionEnum->value,
                'clearingtype'                      => PayoneClearingEnum::WALLET->value,
                'wallettype'                        => 'CTP',
                'cardtype'                          => $this->convertCardType($cardType),
                'cardexpiredate'                    => $this->convertExpiredate($expireDate),
                'add_paydata[paymentcheckout_data]' => $paymentCheckoutData,
                // no pseudocardpan here
            ];
        }

        // default manual / classic card entry
        return [
            'request'        => $requestActionEnum->value,
            'clearingtype'   => PayoneClearingEnum::CREDIT_CARD->value,
            'pseudocardpan'  => $pseudoCardPan,
            'cardtype'       => $this->convertCardType($cardType),
            'cardholder'     => $cardHolder,
            'cardexpiredate' => $this->convertExpiredate($expireDate),
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }

    protected function convertCardType(string $cardType): string
    {
        $cardType = \strtolower($cardType);

        foreach (self::CARD_TYPE_MAP as $value => $needles) {
            foreach ($needles as $needle) {
                if (\str_contains($cardType, $needle)) {
                    return $value;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown card type "%s"', $cardType));
    }

    protected function convertExpiredate(string $exiredate): string
    {
        $month = \substr($exiredate, 0, 2);
        $year  = \substr($exiredate, 2, 2);

        return $year . $month;
    }
}
