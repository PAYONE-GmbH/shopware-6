<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use PayonePayment\StoreApi\Response\MandateResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMandateRoute
{
    abstract public function getDecorated(): AbstractCardRoute;

    abstract public function load(SalesChannelContext $context): MandateResponse;

    abstract public function getFile(string $mandate, SalesChannelContext $context): Response;
}
