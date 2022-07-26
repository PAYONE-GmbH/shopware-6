<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface InstallmentServiceInterface
{
    public function getInstallmentCalculatorData(SalesChannelContext $salesChannelContext, ?RequestDataBag $dataBag = null): ?RatepayInstallmentCalculatorData;
}
