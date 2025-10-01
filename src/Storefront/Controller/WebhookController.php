<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Payone\Webhook\Processor\WebhookProcessorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class WebhookController extends StorefrontController
{
    public function __construct(
        private readonly WebhookProcessorInterface $webhookProcessor,
    ) {
    }

    #[Route(
        path: '/payone/webhook',
        name: 'payone_webhook',
        methods: [ 'POST' ],
    )]
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->webhookProcessor->process($salesChannelContext, $request);
    }
}
