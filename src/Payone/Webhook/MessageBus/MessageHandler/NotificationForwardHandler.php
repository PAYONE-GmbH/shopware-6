<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\MessageHandler;

use PayonePayment\DataAbstractionLayer\Entity\NotificationForward\PayonePaymentNotificationForwardEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class NotificationForwardHandler extends AbstractMessageHandler
{
    /** @var EntityRepositoryInterface */
    private $notificationForwardRepository;

    public function __construct(EntityRepositoryInterface $notificationForwardRepository)
    {
        $this->notificationForwardRepository = $notificationForwardRepository;
    }

    /** @param NotificationForwardCommand $message */
    public function handle($message): void
    {
        $notificationForwards = $this->getNotificationForwards($message->getNotificationTargetIds(), $message->getContext());
        $multiHandle          = curl_multi_init();

        $forwardRequests = $this->getForwardRequests($multiHandle, $notificationForwards);

        do {
            $status = curl_multi_exec($multiHandle, $active);

            if ($active) {
                curl_multi_select($multiHandle);
            }
        } while ($active && $status == CURLM_OK);

        //TODO: implement logging
        $this->updateResponses($multiHandle, $notificationForwards, $forwardRequests, $message->getContext());

        curl_multi_close($multiHandle);
    }

    public static function getHandledMessages(): iterable
    {
        return [
            NotificationForwardCommand::class,
        ];
    }

    private function getNotificationForwards(array $ids, Context $context): EntitySearchResult
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociation('notificationTarget');

        return $this->notificationForwardRepository->search($criteria, $context);
    }

    private function updateResponses($multiHandle, EntitySearchResult $notificationForwards, array $forwardRequests, Context $context): void
    {
        $data = [];

        foreach ($notificationForwards as $forward) {
            $id       = $forward->getId();
            $response = curl_multi_getcontent($forwardRequests[$id]);

            $data[] = [
                'id'       => $id,
                'response' => (!empty($response)) ? $response : 'NO_RESPONSE',
            ];

            curl_multi_remove_handle($multiHandle, $forwardRequests[$id]);
        }

        $this->notificationForwardRepository->update($data, $context);
    }

    private function getForwardRequests($multiHandle, EntitySearchResult $notificationForwards): array
    {
        $forwardRequests = [];

        foreach ($notificationForwards as $forward) {
            /** @var PayonePaymentNotificationForwardEntity $forward */
            $id     = $forward->getId();
            $target = $forward->getNotificationTarget();

            $forwardRequests[$id] = curl_init();

            curl_setopt($forwardRequests[$id], CURLOPT_URL, $target->getUrl());
            curl_setopt($forwardRequests[$id], CURLOPT_HEADER, 0);
            curl_setopt($forwardRequests[$id], CURLOPT_POST, 1);
            curl_setopt($forwardRequests[$id], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($forwardRequests[$id], CURLOPT_TIMEOUT, 10);
            curl_setopt($forwardRequests[$id], CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($forwardRequests[$id], CURLOPT_POSTFIELDS, http_build_query(unserialize($forward->getContent(), [])));
            curl_setopt($forwardRequests[$id], CURLOPT_FAILONERROR, true);
            curl_multi_add_handle($multiHandle, $forwardRequests[$id]);

            if ($target->isBasicAuth() === false) {
                continue;
            }

            $headers = [
                'Content-Type:application/json',
                'Authorization: Basic ' . base64_encode($target->getUsername() . ':' . $target->getPassword()),
            ];
            curl_setopt($forwardRequests[$id], CURLOPT_HTTPHEADER, $headers);
        }

        return $forwardRequests;
    }
}
