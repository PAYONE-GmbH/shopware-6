<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Struct\Struct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $pseudoCardPan      = $arguments->getRequestData()->get('pseudoCardPan');
        $savedPseudoCardPan = $arguments->getRequestData()->get('savedPseudoCardPan');

        if (!empty($savedPseudoCardPan)) {
            $pseudoCardPan = $savedPseudoCardPan;
        }

        return [
            'clearingtype'  => 'cc',
            'request'       => 'authorization',
            'pseudocardpan' => $pseudoCardPan,
        ];
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneCreditCardPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
