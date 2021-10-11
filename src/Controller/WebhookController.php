<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use PayonePayment\Payone\Webhook\Processor\WebhookProcessorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends StorefrontController
{
    /** @var WebhookProcessorInterface */
    private $webhookProcessor;

    /** @var EntityRepositoryInterface */
    private $notificationForwardRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(
        WebhookProcessorInterface $webhookProcessor,
        EntityRepositoryInterface $notificationForwardRepository,
        MessageBusInterface $messageBus
    ) {
        $this->webhookProcessor              = $webhookProcessor;
        $this->notificationForwardRepository = $notificationForwardRepository;
        $this->messageBus                    = $messageBus;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/webhook", name="payone_webhook", defaults={"csrf_protected": false}, methods={"POST"})
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->webhookProcessor->process($salesChannelContext, $request);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/payone/requeue-forward", name="api.action.payone.requeue.forward", methods={"POST"})
     * @Route("/api/v{version}/_action/payone/requeue-forward", name="api.action.payone.requeue.forward.legacy", methods={"POST"})
     */
    public function reQueueForward(Request $request, Context $context): Response
    {
        $id    = $request->get('notificationForwardId');
        $newId = Uuid::randomHex();

        $this->notificationForwardRepository->clone($id, $context, $newId);
        $this->messageBus->dispatch(new NotificationForwardCommand([$newId], $context));

        return $this->createActionResponse($request);
    }
}
