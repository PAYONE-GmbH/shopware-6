<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class ManageMandateRequestFactory extends AbstractRequestFactory
{
    /** @var ManageMandateRequest */
    private $mandateRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        ManageMandateRequest $mandateRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->mandateRequest = $mandateRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $iban         = $dataBag->get('iban');
        $bic          = $dataBag->get('bic');

        $this->requests[] = $this->mandateRequest->getRequestParameters(
            $transaction,
            $iban,
            $bic,
            $context
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $transaction->getOrder(),
            $context
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId()
        );

        return $this->createRequest();
    }
}
