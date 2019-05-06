<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Payone\Webhook\Processor\WebhookProcessor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends StorefrontController
{
    /**
     * @Route("/payone/webhook", name="payone_webhook", methods={"POST"})
     *
     * @param Request             $request
     * @param SalesChannelContext $salesChannelContext
     *
     * @return Response
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $webhookProcessor = $this->container->get(WebhookProcessor::class);

        return $webhookProcessor->process($salesChannelContext, $request->request->all());
    }
}
