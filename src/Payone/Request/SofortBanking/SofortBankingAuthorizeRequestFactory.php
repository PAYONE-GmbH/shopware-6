<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SofortBanking;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SofortBankingAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var SofortBankingAuthorizeRequest */
    private $authorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        SofortBankingAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->authorizeRequest = $authorizeRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->authorizeRequest->getRequestParameters(
            $transaction,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            SystemRequest::CONFIGURATION_PREFIX_SOFORT
        );

        return $this->createRequest();
    }
}
