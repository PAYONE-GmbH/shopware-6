<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface InstallmentServiceInterface
{
    public function getInstallmentCalculatorData(?RequestDataBag $dataBag = null): RatepayInstallmentCalculatorData;
}
