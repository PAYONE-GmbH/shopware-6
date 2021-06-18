<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

trait RequestDataTrait
{
    /** @var RequestDataBag */
    protected $requestData;

    public function getRequestData(): RequestDataBag
    {
        return $this->requestData;
    }

    public function setRequestData(RequestDataBag $requestData): void
    {
        $this->requestData = $requestData;
    }
}
