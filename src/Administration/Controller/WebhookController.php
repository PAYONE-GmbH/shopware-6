<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Controller;

use PayonePayment\DataAbstractionLayer\Entity\NotificationForward\PayonePaymentNotificationForwardEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

#[Route(defaults: [ '_routeScope' => [ 'api' ] ])]
class WebhookController extends StorefrontController
{
    private readonly Serializer $serializer;

    public function __construct(
        private readonly EntityRepository $notificationForwardRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        $this->serializer = new Serializer([], [ new JsonEncoder() ]);
    }

    #[Route(
        path: '/api/_action/payone/requeue-forward',
        name: 'api.action.payone.requeue.forward',
        methods: [ 'POST' ],
    )]
    public function reQueueForward(Request $request, Context $context): Response
    {
        $id = $request->get('notificationForwardId');

        /** @var PayonePaymentNotificationForwardEntity|null $entity */
        $entity = $this->notificationForwardRepository->search(new Criteria([ $id ]), $context)->first();

        if ($entity instanceof PayonePaymentNotificationForwardEntity) {
            $message = new NotificationForwardMessage(
                $entity->getNotificationTargetId(),
                $this->serializer->decode($entity->getContent(), JsonEncoder::FORMAT),
                $entity->getTransactionId(),
                $entity->getIp(),
            );

            $this->messageBus->dispatch($message);
        }

        return $this->createActionResponse($request);
    }
}
