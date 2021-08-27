<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use PayonePayment\StoreApi\Response\CardResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

abstract class AbstractCardRoute
{
    abstract public function getDecorated(): AbstractCardRoute;

    abstract public function load(SalesChannelContext $context): CardResponse;

    abstract public function delete(string $pseudoCardPan, SalesChannelContext $context): StoreApiResponse;
}
