<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\RequestFactoryInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\SalesChannelCartController;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalAuthorizeRequestFactory extends AbstractRequestFactory implements RequestFactoryInterface
{
    /** @var PaypalAuthorizeRequest */
    private $authorizeRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PaypalAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->authorizeRequest = $authorizeRequest;
        $this->customerRequest  = $customerRequest;
        $this->systemRequest    = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $this->requests[] = $this->authorizeRequest->getRequestParameters(
            $transaction,
            $context
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $transaction->getOrder(),
            $context
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannel(),
            $context
        );

        return $this->createRequest();
    }
}
