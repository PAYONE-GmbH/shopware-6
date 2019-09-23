<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManageMandateRequestFactory extends AbstractRequestFactory
{
    /** @var ManageMandateRequest */
    private $mandateRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        ManageMandateRequest $mandateRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->mandateRequest  = $mandateRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        SalesChannelContext $context,
        string $iban,
        string $bic
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_DEBIT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->mandateRequest->getRequestParameters(
            $context,
            $iban,
            $bic
        );

        return $this->createRequest();
    }
}
