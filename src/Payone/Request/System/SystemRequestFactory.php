<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\RequestFactoryInterface;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SystemRequestFactory extends AbstractRequestFactory implements RequestFactoryInterface
{
    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(SystemRequest $systemRequest)
    {
        $this->systemRequest = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannel(),
            $context->getContext()
        );

        return $this->createRequest();
    }
}
