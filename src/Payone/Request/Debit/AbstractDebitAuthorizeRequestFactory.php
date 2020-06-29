<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractDebitAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractDebitAuthorizeRequest */
    private $debitRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractDebitAuthorizeRequest $debitRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->debitRequest    = $debitRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $iban         = $dataBag->get('iban');
        $bic          = $dataBag->get('bic');
        $accountOwner = $dataBag->get('accountOwner');

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_DEBIT,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->debitRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $iban,
            $bic,
            $accountOwner
        );

        return $this->createRequest();
    }
}
