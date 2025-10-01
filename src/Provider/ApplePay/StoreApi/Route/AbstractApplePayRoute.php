<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\StoreApi\Route;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApplePayRoute
{
    abstract public function getDecorated(): AbstractApplePayRoute;

    abstract public function validateMerchant(Request $request, SalesChannelContext $salesChannelContext): Response;

    abstract public function process(Request $request, SalesChannelContext $salesChannelContext): Response;
}
