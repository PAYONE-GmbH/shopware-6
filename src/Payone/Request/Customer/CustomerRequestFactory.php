<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerRequestFactory extends AbstractRequestFactory
{
    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CustomerRequest $customerRequest, SystemRequest $systemRequest)
    {
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        SalesChannelContext $context
    ): array {
        if (null !== $transaction->getOrderTransaction()->getPaymentMethod()) {
            $this->requests[] = $this->systemRequest->getRequestParameters(
                $transaction->getOrder()->getSalesChannelId(),
                ConfigurationPrefixes::CONFIGURATION_PREFIXES[$transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier()],
                $context->getContext()
            );
        }

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        return $this->createRequest();
    }
}
