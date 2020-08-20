<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Eps;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractEpsAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractEpsAuthorizeRequest */
    private $epsRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractEpsAuthorizeRequest $epsRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->epsRequest      = $epsRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_EPS,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $referenceNumber = $this->systemRequest->getReferenceNumber($transaction, true);

        $this->requests[] = $this->epsRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context->getContext(),
            $referenceNumber
        );

        return $this->createRequest();
    }
}
