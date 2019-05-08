<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCardCheck;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CreditCardCheckRequestFactory extends AbstractRequestFactory
{
    /** @var CreditCardCheckRequest */
    private $creditCardRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CreditCardCheckRequest $creditCardCheckRequest, SystemRequest $systemRequest)
    {
        $this->creditCardRequest = $creditCardCheckRequest;
        $this->systemRequest   = $systemRequest;
    }

    public function getRequestParameters(SalesChannelEntity $salesChannelEntity, Context $context): array
    {
        $this->requests[] = $this->creditCardRequest->getRequestParameters();

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $salesChannelEntity,
            $context
        );

        return $this->createRequest();
    }
}
