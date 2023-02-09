<?php

declare(strict_types=1);

namespace PayonePayment\Components\SecuredInstallment;

use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface InstallmentServiceInterface
{
    public function getInstallmentOptions(SalesChannelContext $salesChannelContext, ?RequestDataBag $dataBag = null): SecuredInstallmentOptionsData;
}
