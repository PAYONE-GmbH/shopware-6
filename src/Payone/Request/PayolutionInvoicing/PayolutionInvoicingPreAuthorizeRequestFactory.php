<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInvoicing;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInvoicingPreAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var PayolutionInvoicingPreAuthorizeRequest */
    private $authorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PayolutionInvoicingPreAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->authorizeRequest = $authorizeRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYOLUTION_INVOICING,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->authorizeRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context->getContext()
        );

        return $this->createRequest();
    }
}
