<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Debit;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class DebitAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var DebitAuthorizeRequest */
    private $authorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        DebitAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->authorizeRequest = $authorizeRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $iban         = $dataBag->getAlpha('iban');
        $bic          = $dataBag->getAlpha('bic');
        $accountOwner = $dataBag->getAlpha('accountOwner');

        $this->requests[] = $this->authorizeRequest->getRequestParameters(
            $transaction,
            $context,
            $iban,
            $bic,
            $accountOwner
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
