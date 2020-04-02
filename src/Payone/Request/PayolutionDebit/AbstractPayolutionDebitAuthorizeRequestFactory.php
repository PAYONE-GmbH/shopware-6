<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionDebit;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPayolutionDebitAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractPayolutionDebitAuthorizeRequest */
    private $payolutionDebitRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractPayolutionDebitAuthorizeRequest $payolutionDebitRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->payolutionDebitRequest = $payolutionDebitRequest;
        $this->customerRequest        = $customerRequest;
        $this->systemRequest          = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYOLUTION_DEBIT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->payolutionDebitRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context
        );

        return $this->createRequest();
    }
}
