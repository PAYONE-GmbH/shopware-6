<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Invoice;

use PayonePayment\Components\RequestBuilder\InvoiceRequestBuilder;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractInvoiceAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractInvoiceAuthorizeRequest */
    private $invoiceRequest;

    /** @var InvoiceRequestBuilder */
    private $invoiceRequestBuilder;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractInvoiceAuthorizeRequest $invoiceRequest,
        InvoiceRequestBuilder $invoiceRequestBuilder,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->invoiceRequest        = $invoiceRequest;
        $this->invoiceRequestBuilder = $invoiceRequestBuilder;
        $this->customerRequest       = $customerRequest;
        $this->systemRequest         = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_INVOICE,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $referenceNumber = $this->systemRequest->getReferenceNumber($transaction, true);

        $this->requests[] = $this->invoiceRequest->getRequestParameters(
            $transaction,
            $dataBag,
            $context,
            $referenceNumber
        );

        $this->requests[] = $this->invoiceRequestBuilder->getAdditionalRequestParameters(
            $transaction,
            $context->getContext(),
            $dataBag
        );

        return $this->createRequest();
    }
}
