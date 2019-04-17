<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Payone\Webhook\Processor\WebhookProcessor;
use Shopware\Storefront\Framework\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PayoneWebhookController extends StorefrontController
{
    /**
     * @Route("/payone_webhook/execute", name="payone_webhook_execute")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function execute(Request $request): Response
    {
        if ($request->getRealMethod() !== 'POST') {
            return new Response('Method not allowed', Response::HTTP_NOT_FOUND);
        }

        $webhookProcessor = $this->container->get(WebhookProcessor::class);

        return $webhookProcessor->process($request->query->all());
    }
}
