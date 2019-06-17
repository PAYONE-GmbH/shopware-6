<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCardCheck;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\RequestFactoryInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardCheckRequestFactory extends AbstractRequestFactory implements RequestFactoryInterface
{
    /** @var CreditCardCheckRequest */
    private $creditCardRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CreditCardCheckRequest $creditCardCheckRequest, SystemRequest $systemRequest)
    {
        $this->creditCardRequest = $creditCardCheckRequest;
        $this->systemRequest     = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $this->requests[] = $this->creditCardRequest->getRequestParameters();

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel(),
            $context
        );

        return $this->createRequest();
    }
}
