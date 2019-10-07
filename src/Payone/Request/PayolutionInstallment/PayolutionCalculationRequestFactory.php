<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInstallment;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionCalculationRequestFactory extends AbstractRequestFactory
{
    /** @var PayolutionCalculationRequest */
    private $calculationRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PayolutionCalculationRequest $calculationRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->calculationRequest = $calculationRequest;
        $this->customerRequest    = $customerRequest;
        $this->systemRequest      = $systemRequest;
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

        $this->requests[] = $this->calculationRequest->getRequestParameters(
            $cart,
            $dataBag,
            $context->getContext()
        );

        return $this->createRequest();
    }
}
