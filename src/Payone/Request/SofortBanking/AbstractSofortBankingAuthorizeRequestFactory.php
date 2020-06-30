<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SofortBanking;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractSofortBankingAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractSofortBankingAuthorizeRequest */
    private $sofortBankingRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        AbstractSofortBankingAuthorizeRequest $sofortBankingRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->sofortBankingRequest = $sofortBankingRequest;
        $this->customerRequest      = $customerRequest;
        $this->systemRequest        = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        SalesChannelContext $context
    ): array {
        if (null !== $transaction->getOrder()) {
            $this->requests[] = $this->systemRequest->getRequestParameters(
                $transaction->getOrder()->getSalesChannelId(),
                ConfigurationPrefixes::CONFIGURATION_PREFIX_SOFORT,
                $context->getContext()
            );
        }

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->sofortBankingRequest->getRequestParameters(
            $transaction,
            $context->getContext()
        );

        return $this->createRequest();
    }
}
