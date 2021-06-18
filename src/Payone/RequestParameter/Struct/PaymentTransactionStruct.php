<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentTransactionStruct extends Struct
{
    use DeterminationTrait;
    use SalesChannelContextTrait;
    use RequestDataTrait;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

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

    public function getPaymentTransaction(): PaymentTransaction
    {
        return $this->paymentTransaction;
    }

    public function setPaymentTransaction(PaymentTransaction $paymentTransaction): void
    {
        $this->paymentTransaction = $paymentTransaction;
    }
}
