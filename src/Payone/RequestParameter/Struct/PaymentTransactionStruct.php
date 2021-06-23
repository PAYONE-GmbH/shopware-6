<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\TransactionTrait;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentTransactionStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use RequestDataTrait;
    use TransactionTrait;

    public function __construct(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = '')
    {
        $this->paymentTransaction  = $paymentTransaction;
        $this->requestData         = $requestData;
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod       = $paymentMethod;
        $this->action              = $action;
    }
}
