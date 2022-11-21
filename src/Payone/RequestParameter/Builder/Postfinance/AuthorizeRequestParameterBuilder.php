<?php declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Postfinance;

use PayonePayment\PaymentHandler\PayonePostfinanceCardPaymentHandler;
use PayonePayment\PaymentHandler\PayonePostfinanceWalletPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        switch ($arguments->getPaymentMethod()) {
            case PayonePostfinanceWalletPaymentHandler::class:
                $type = self::ONLINEBANK_TRANSFER_TYPE_WALLET;

                break;
            case PayonePostfinanceCardPaymentHandler::class:
                $type = self::ONLINEBANK_TRANSFER_TYPE_CARD;

                break;
            default:
                throw new \RuntimeException('Invalid payment method handler');
        }

        $billingAddress = $arguments->getPaymentTransaction()->getOrder()->getBillingAddress();

        return [
            'request' => $arguments->getAction(),
            'clearingtype' => self::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
            'onlinebanktransfertype' => $type,
            'bankcountry' => 'CH',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof PaymentTransactionStruct;
    }
}
