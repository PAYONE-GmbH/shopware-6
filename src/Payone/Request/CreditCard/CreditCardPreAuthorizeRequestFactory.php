<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CreditCardPreAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var CreditCardPreAuthorizeRequest */
    private $preAuthorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        CreditCardPreAuthorizeRequest $preAuthorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->preAuthorizeRequest = $preAuthorizeRequest;
        $this->customerRequest     = $customerRequest;
        $this->systemRequest       = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $pseudoPan = $dataBag->get('pseudocardpan');

        $this->requests[] = $this->preAuthorizeRequest->getRequestParameters(
            $transaction,
            $context,
            $pseudoPan
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $transaction->getOrder(),
            $context
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId()
        );

        return $this->createRequest();
    }
}
