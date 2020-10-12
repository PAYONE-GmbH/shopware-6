<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SecureInvoice;

use PayonePayment\Components\RequestBuilder\SecureInvoiceRequestBuilder;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractSecureInvoiceAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractSecureInvoiceAuthorizeRequest */
    private $secureInvoiceRequest;

    /** @var SecureInvoiceRequestBuilder */
    private $secureInvoiceRequestBuilder;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractSecureInvoiceAuthorizeRequest $secureInvoiceRequest,
        SecureInvoiceRequestBuilder $secureInvoiceRequestBuilder,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->secureInvoiceRequest        = $secureInvoiceRequest;
        $this->secureInvoiceRequestBuilder = $secureInvoiceRequestBuilder;
        $this->customerRequest             = $customerRequest;
        $this->systemRequest               = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_SECURE_INVOICE,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->secureInvoiceRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context,
            $this->systemRequest->getReferenceNumber($transaction, true)
        );

        $this->requests[] = $this->secureInvoiceRequestBuilder->getAdditionalRequestParameters(
            $transaction,
            $context->getContext(),
            $dataBag
        );

        return $this->createRequest();
    }
}
