<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInvoicing;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInvoicingPreCheckRequestFactory extends AbstractRequestFactory
{
    /** @var PayolutionInvoicingPreCheckRequest */
    private $checkRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PayolutionInvoicingPreCheckRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->checkRequest    = $authorizeRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        Cart $cart,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYOLUTION_INSTALLMENT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->checkRequest->getRequestParameters(
            $cart,
            $dataBag,
            $context
        );

        return $this->createRequest();
    }
}
