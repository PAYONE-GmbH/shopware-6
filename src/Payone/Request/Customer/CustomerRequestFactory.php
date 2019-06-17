<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\RequestFactoryInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerRequestFactory extends AbstractRequestFactory implements RequestFactoryInterface
{
    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CustomerRequest $customerRequest, SystemRequest $systemRequest)
    {
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->customerRequest->getRequestParameters(
            $transaction->getOrder(),
            $context->getContext()
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannel(),
            $context->getContext()
        );

        return $this->createRequest();
    }
}
