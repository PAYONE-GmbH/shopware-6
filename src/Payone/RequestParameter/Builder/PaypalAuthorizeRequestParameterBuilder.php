<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;

class PaypalAuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder {
    public function getRequestParameter(RequestContentStruct $requestContent, Context $context) : array {
        $transaction = $requestContent->getPaymentTransaction();
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $parameters = [
            'request' => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => $this->getConvertedAmount($transaction->getOrder()->getAmountTotal()),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $requestContent->getReferenceNumber(),
            'successurl'   => $this->encodeUrl($transaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->encodeUrl($transaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->encodeUrl($transaction->getReturnUrl() . '&state=cancel'),
            'workorderid'  => $requestContent->getWorkOrderId(),
        ];

        //TODO: applyShippingParameter
        //TODO: narrative_text
        //TODO: workorderID / carthasher

        return $parameters;
    }

    public function supports(RequestContentStruct $requestContent) : bool {
        return ($requestContent->getPaymentMethod() === PayonePaypal::class && $requestContent->getAction() === 'authorize');
    }

    public function validate(RequestContentStruct $requestContent) : void {
        if(null === $requestContent->getPaymentTransaction()) {
            throw new InvalidRequestParameterException('paymentTransaction');
        }

        if(null === $requestContent->getWorkOrderId()) {
            throw new InvalidRequestParameterException('workOrderId');
        }

        if(null === $requestContent->getPaymentTransaction()->getOrder()) {
            throw new InvalidRequestParameterException('order');
        }

        if(null === $requestContent->getAmount()) {
            throw new InvalidRequestParameterException('amount');
        }

        if(null === $requestContent->getReferenceNumber()) {
            throw new InvalidRequestParameterException('referenceNumber');
        }
    }
}
