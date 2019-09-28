<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaysafeAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var PaysafelAuthorizeRequest */
    private $authorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PaysafelAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->authorizeRequest = $authorizeRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        SalesChannelContext $context,
        ?string $workOrderId = null
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYPAL,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->authorizeRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $workOrderId
        );

        return $this->createRequest();
    }
}
