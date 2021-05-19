<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;

class PaypalPreAuthorizeRequestParameterBuilder extends PaypalAuthorizeRequestParameterBuilder {
    public function getRequestParameter(RequestContentStruct $requestContent, Context $context) : array {
        return array_merge(parent::getRequestParameters($requestContent, $context), [
            'request' => 'preauthorization',
        ]);
    }

    public function supports(RequestContentStruct $requestContent) : bool {
        return ($requestContent->getPaymentMethod() === PayonePaypal::class && $requestContent->getAction() === 'preauthorize');
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
