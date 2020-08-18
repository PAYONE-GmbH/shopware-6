<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Prepayment;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPrepaymentAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractPrepaymentAuthorizeRequest */
    private $prepaymentRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractPrepaymentAuthorizeRequest $prepaymentRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->prepaymentRequest = $prepaymentRequest;
        $this->customerRequest   = $customerRequest;
        $this->systemRequest     = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PREPAYMENT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $referenceNumber = $this->systemRequest->getReferenceNumber($transaction, true);

        $this->requests[] = $this->prepaymentRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context,
            $referenceNumber
        );

        return $this->createRequest();
    }
}
