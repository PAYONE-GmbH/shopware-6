<?php

declare(strict_types=1);

namespace PayonePayment\Components\ApplePay\Controller;

use PayonePayment\Components\ApplePay\StoreApi\Route\AbstractApplePayRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: '/payone/apple-pay/',
    options: ['seo' => false],
    defaults: [
        'XmlHttpRequest' => true,
        '_routeScope' => ['storefront'],
        '_loginRequired' => true,
        '_loginRequiredAllowGuest' => true,
    ],
)]
class CheckoutController
{
    public function __construct(
        private readonly AbstractApplePayRoute $route
    ) {
    }

    #[Route(path: 'validate-merchant', name: 'frontend.payone.apple-pay.validate-merchant', methods: ['POST'])]
    public function validateMerchant(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->route->validateMerchant($request, $salesChannelContext);
    }

    #[Route(path: 'process-payment', name: 'frontend.payone.apple-pay.process-payment', methods: ['POST'])]
    public function processPayment(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->route->process($request, $salesChannelContext);
    }
}
