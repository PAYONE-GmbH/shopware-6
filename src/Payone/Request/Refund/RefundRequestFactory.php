<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Refund;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;

class RefundRequestFactory extends AbstractRequestFactory
{
    /** @var SystemRequest */
    private $systemRequest;

    /** @var RefundRequest */
    private $refundRequest;

    public function __construct(SystemRequest $systemRequest, RefundRequest $refundRequest)
    {
        $this->systemRequest = $systemRequest;
        $this->refundRequest = $refundRequest;
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        $this->requests[] = $this->refundRequest->getRequestParameters(
            $transaction->getOrder(),
            $transaction->getCustomFields()
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannel(),
            $context
        );

        return $this->createRequest();
    }
}
