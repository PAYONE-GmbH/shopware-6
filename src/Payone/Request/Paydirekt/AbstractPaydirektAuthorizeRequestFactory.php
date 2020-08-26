<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paydirekt;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPaydirektAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractPaydirektAuthorizeRequest */
    private $paydirektRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractPaydirektAuthorizeRequest $paydirektRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->paydirektRequest = $paydirektRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYDIREKT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        // todo: do we need logic here to select the billing address alternatively?
        $shippingAddress = $context->getCustomer()->getActiveShippingAddress();

        $this->requests[] = $this->paydirektRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $shippingAddress
        );

        return $this->createRequest();
    }
}
