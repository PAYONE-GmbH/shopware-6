<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $cardType = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_TYPE);
        $pseudoCardPan = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_PSEUDO_CARD_PAN);
        $savedPseudoCardPan = $arguments->getRequestData()->get(PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN);

        if (!empty($savedPseudoCardPan)) {
            $pseudoCardPan = $savedPseudoCardPan;
        }

        return [
            'clearingtype' => self::CLEARING_TYPE_CREDIT_CARD,
            'request' => self::REQUEST_ACTION_AUTHORIZE,
            'pseudocardpan' => $pseudoCardPan,
            'cardtype' => $cardType,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneCreditCardPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
