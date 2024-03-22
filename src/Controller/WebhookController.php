<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\DataAbstractionLayer\Entity\NotificationForward\PayonePaymentNotificationForwardEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardMessage;
use PayonePayment\Payone\Webhook\Processor\WebhookProcessorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends StorefrontController
{
    public function __construct(
        private readonly WebhookProcessorInterface $webhookProcessor,
        private readonly EntityRepository $notificationForwardRepository,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route(path: '/payone/webhook', name: 'payone_webhook', defaults: ['_routeScope' => ['storefront']], methods: ['POST'])]
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->webhookProcessor->process($salesChannelContext, $request);
    }

    #[Route(path: '/api/_action/payone/requeue-forward', name: 'api.action.payone.requeue.forward', defaults: ['_routeScope' => ['api']], methods: ['POST'])]
    public function reQueueForward(Request $request, Context $context): Response
    {
        $id = $request->get('notificationForwardId');

        /** @var PayonePaymentNotificationForwardEntity|null $entity */
        $entity = $this->notificationForwardRepository->search(new Criteria([$id]), $context)->first();
        if ($entity instanceof PayonePaymentNotificationForwardEntity) {
            $message = new NotificationForwardMessage(
                $entity->getNotificationTargetId(),
                json_decode($entity->getContent(), true),
                $entity->getTransactionId(),
                $entity->getIp()
            );
            $this->messageBus->dispatch($message);
        }

        return $this->createActionResponse($request);
    }
}
