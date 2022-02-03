<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\MessageHandler;

use PayonePayment\DataAbstractionLayer\Entity\NotificationForward\PayonePaymentNotificationForwardEntity;
use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class NotificationForwardHandler extends AbstractMessageHandler
{
    /** @var EntityRepositoryInterface */
    private $notificationForwardRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityRepositoryInterface $notificationForwardRepository, LoggerInterface $logger)
    {
        $this->notificationForwardRepository = $notificationForwardRepository;
        $this->logger                        = $logger;
    }

    /** @param NotificationForwardCommand $message */
    public function handle($message): void
    {
        $notificationForwards = $this->getNotificationForwards($message->getNotificationTargetIds(), $message->getContext());
        /** @var resource $multiHandle */
        $multiHandle = curl_multi_init();

        $this->logger->info('Forwarding notifications', array_keys($notificationForwards->getElements()));

        $forwardRequests = $this->getForwardRequests($multiHandle, $notificationForwards);

        if (empty($forwardRequests)) {
            curl_multi_close($multiHandle);

            return;
        }

        do {
            $status = curl_multi_exec($multiHandle, $active);

            if ($active) {
                curl_multi_select($multiHandle);
            }
        } while ($active && $status === CURLM_OK);

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

    /**
     * @param resource $multiHandle
     */
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

    /**
     * @param resource $multiHandle
     */
    private function getForwardRequests($multiHandle, EntitySearchResult $notificationForwards): array
    {
        $forwardRequests = [];

        foreach ($notificationForwards as $forward) {
            $id = $forward->getId();

            /** @var PayonePaymentNotificationForwardEntity $forward */
            $target = $forward->getNotificationTarget();

            if (null === $target) {
                continue;
            }

            $forwardRequests[$id] = curl_init();

            $serialize = unserialize($forward->getContent(), []);
            /** @var array $content */
            $content = mb_convert_encoding($serialize, 'ISO-8859-1', 'UTF-8');

            curl_setopt($forwardRequests[$id], CURLOPT_URL, $target->getUrl());
            curl_setopt($forwardRequests[$id], CURLOPT_HEADER, 0);
            curl_setopt($forwardRequests[$id], CURLOPT_POST, 1);
            curl_setopt($forwardRequests[$id], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($forwardRequests[$id], CURLOPT_TIMEOUT, 10);
            curl_setopt($forwardRequests[$id], CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($forwardRequests[$id], CURLOPT_POSTFIELDS, http_build_query($content));
            curl_setopt($forwardRequests[$id], CURLOPT_FAILONERROR, true);
            curl_setopt($forwardRequests[$id], CURLOPT_HTTPHEADER, $this->buildHeaders($forward, $target));

            curl_multi_add_handle($multiHandle, $forwardRequests[$id]);
        }

        return $forwardRequests;
    }

    private function buildHeaders(
        PayonePaymentNotificationForwardEntity $forward,
        PayonePaymentNotificationTargetEntity $target
    ): array {
        $headers = [
            'X-Forwarded-For: ' . $forward->getIp(),
        ];

        if ($target->isBasicAuth() === true) {
            $headers[] = 'Content-Type:application/json';
            $headers[] = 'Authorization: Basic ' . base64_encode($target->getUsername() . ':' . $target->getPassword());
        }

        return $headers;
    }
}
