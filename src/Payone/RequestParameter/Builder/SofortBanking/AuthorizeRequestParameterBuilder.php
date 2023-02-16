<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SofortBanking;

use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $billingAddress = $arguments->getPaymentTransaction()->getOrder()->getBillingAddress();

        if ($billingAddress === null || $billingAddress->getCountry() === null) {
            // should never occur
            throw new SyncPaymentProcessException($arguments->getPaymentTransaction()->getOrderTransaction()->getId(), 'Internal error. Country is not given for processing Payone payment');
        }

        return [
            'clearingtype' => self::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
            'onlinebanktransfertype' => 'PNT',
            'bankcountry' => $billingAddress->getCountry()->getIso(),
            'request' => self::REQUEST_ACTION_AUTHORIZE,
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneSofortBankingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
