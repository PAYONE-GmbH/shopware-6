<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

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
        string $pseudoCardPan,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->preAuthorizeRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $pseudoCardPan
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            SystemRequest::CONFIGURATION_PREFIX_CREDITCARD
        );

        return $this->createRequest();
    }
}
