<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct\Traits;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait SalesChannelContextTrait
{
    /** @var SalesChannelContext */
    protected $salesChannelContext;

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
