<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalAuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder {
    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ) : array {
        $currency = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());

        //TODO: handle systemRequest parameters -> priority at first
        //TODO: handle customerRequest parameters -> priority before this

        //TODO: get reference Number

        $parameters = [
            'request' => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => $this->getConvertedAmount($paymentTransaction->getOrder()->getAmountTotal()),
            'currency'     => $currency->getIsoCode(),
            'reference'    => 'TODO: set referencenumber',
            'successurl'   => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=cancel'),
            'workorderid'  => 'TODO: set workorderid',
        ];



        //TODO: applyShippingParameter
        //TODO: narrative_text
        //TODO: workorderID / carthasher

        return $parameters;
    }

    public function supports(string $paymentMethod, string $action = '') : bool {
        return ($paymentMethod === PayonePaypal::class && $action === 'authorize');
    }
}
