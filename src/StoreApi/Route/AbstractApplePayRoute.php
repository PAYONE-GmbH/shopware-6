<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApplePayRoute
{
    abstract public function validateMerchant(Request $request, SalesChannelContext $context): Response;

    abstract public function process(SalesChannelContext $context): Response;
}
