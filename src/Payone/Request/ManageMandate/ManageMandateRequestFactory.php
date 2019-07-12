<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManageMandateRequestFactory extends AbstractRequestFactory
{
    /** @var ManageMandateRequest */
    private $mandateRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        ManageMandateRequest $mandateRequest,
        SystemRequest $systemRequest
    ) {
        $this->mandateRequest  = $mandateRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        SalesChannelContext $context,
        string $iban,
        string $bic
    ): array {
        $this->requests[] = $this->mandateRequest->getRequestParameters(
            $iban,
            $bic,
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel()->getId()
        );

        return $this->createRequest();
    }
}
