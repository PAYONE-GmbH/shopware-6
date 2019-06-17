<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\RequestFactoryInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardPreAuthorizeRequestFactory extends AbstractRequestFactory implements RequestFactoryInterface
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
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $pseudoPan = $dataBag->get('pseudocardpan');

        $this->requests[] = $this->preAuthorizeRequest->getRequestParameters(
            $transaction,
            $pseudoPan,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $transaction->getOrder(),
            $context->getContext()
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel(),
            $context->getContext()
        );

        return $this->createRequest();
    }
}
