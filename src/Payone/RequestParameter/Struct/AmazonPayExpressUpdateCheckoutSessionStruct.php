<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\WorkOrderIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AmazonPayExpressUpdateCheckoutSessionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use WorkOrderIdTrait;

    public function __construct(SalesChannelContext $context, string $workOrderId)
    {
        $this->salesChannelContext = $context;
        $this->workorderId = $workOrderId;
        $this->paymentMethod = PayoneAmazonPayExpressPaymentHandler::class;
    }
}
