<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Trustly;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractTrustlyAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractTrustlyAuthorizeRequest */
    private $trustlyRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractTrustlyAuthorizeRequest $trustlyRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->trustlyRequest  = $trustlyRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $iban = $this->validateIbanRequestParameter($dataBag, $transaction);

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_TRUSTLY,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $referenceNumber = $this->systemRequest->getReferenceNumber($transaction, true);

        $this->requests[] = $this->trustlyRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $referenceNumber,
            $iban
        );

        return $this->createRequest();
    }

    private function validateIbanRequestParameter(RequestDataBag $dataBag, PaymentTransaction $transaction): string
    {
        $iban = $dataBag->get('iban');

        if (empty($iban) || !is_string($iban)) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'Missing iban parameter.');
        }

        return $iban;
    }
}
