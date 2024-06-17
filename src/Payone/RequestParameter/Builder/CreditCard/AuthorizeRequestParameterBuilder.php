<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\Components\CardRepository\CardRepository;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly CardRepository $cardRepository
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cardHolder = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_HOLDER);
        $cardType = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_TYPE);
        $pseudoCardPan = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_PSEUDO_CARD_PAN);
        $savedPseudoCardPan = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN);

        $salesChannelContext = $arguments->getSalesChannelContext();
        if (!empty($savedPseudoCardPan) && !empty($salesChannelContext->getCustomerId())) {
            $savedCard = $this->cardRepository->getExistingCard(
                $salesChannelContext->getCustomerId(),
                $savedPseudoCardPan,
                $salesChannelContext->getContext()
            );
            $pseudoCardPan = $savedCard?->getPseudoCardPan() ?: '';
            $cardHolder = $savedCard?->getCardHolder() ?: $cardHolder;
        }

        return [
            'clearingtype' => self::CLEARING_TYPE_CREDIT_CARD,
            'request' => $arguments->getAction(),
            'pseudocardpan' => $pseudoCardPan,
            'cardtype' => $cardType,
            'cardholder' => $cardHolder,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        return $arguments->getPaymentMethod() === PayoneCreditCardPaymentHandler::class
            && \in_array($arguments->getAction(), [self::REQUEST_ACTION_PREAUTHORIZE, self::REQUEST_ACTION_AUTHORIZE], true);
    }
}
