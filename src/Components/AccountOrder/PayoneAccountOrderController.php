<?php

declare(strict_types=1);

namespace PayonePayment\Components\AccountOrder;

use http\Client\Request;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Recovery\Common\HttpClient\Response;
use Shopware\Storefront\Controller\AccountOrderController;
use Shopware\Storefront\Controller\StorefrontController;

/**
 * @RouteScope(scopes={"storefront"})
 */
class PayoneAccountOrderController extends StorefrontController
{
    /** @var PayoneAccountOrderController */
    private $decoratedService;

    public function __construct(AccountOrderController $decoratedService)
    {
        $this->decoratedService = $decoratedService;
    }

    public function editOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedService->editOrder($orderId, $request, $context);
    }

    public function orderChangePayment(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedService->orderChangePayment($orderId, $request, $context);
    }
}
