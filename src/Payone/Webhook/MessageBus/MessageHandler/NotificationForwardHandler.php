<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\MessageHandler;

use Monolog\Level;
use Monolog\Logger;
use PayonePayment\DataAbstractionLayer\Entity\NotificationForward\PayonePaymentNotificationForwardEntity;
use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class NotificationForwardHandler implements MessageSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $notificationForwardRepository,
        private readonly Logger  $logger
    ) {
    }

    public function __invoke(NotificationForwardCommand $message): void
    {
        $this->handle($message);
    }

    public function handle(NotificationForwardCommand $message): void
    {
        $notificationForwards = $this->getNotificationForwards($message->getNotificationTargetIds(), $message->getContext());
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
        } while ($active && $status === \CURLM_OK);

        $this->updateResponses($multiHandle, $notificationForwards, $forwardRequests, $message->getContext());

        foreach ($forwardRequests as $id => $handle) {
            $responseInfo = curl_getinfo($handle);
            $responseContent = curl_multi_getcontent($handle);
            $this->statusLogger($responseInfo, $responseContent, $id);
            curl_multi_remove_handle($multiHandle, $handle);
            curl_close($handle);
        }

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

    private function updateResponses(
        \CurlMultiHandle   $multiHandle,
        EntitySearchResult $notificationForwards,
        array              $forwardRequests,
        Context            $context
    ): void {
        $data = [];

        /** @var PayonePaymentNotificationForwardEntity $forward */
        foreach ($notificationForwards as $forward) {
            $id = $forward->getId();
            $response = curl_multi_getcontent($forwardRequests[$id]);

            $data[] = [
                'id' => $id,
                'response' => (!empty($response)) ? $response : 'NO_RESPONSE',
            ];

            curl_multi_remove_handle($multiHandle, $forwardRequests[$id]);
        }

        $this->notificationForwardRepository->update($data, $context);
    }

    private function getForwardRequests(\CurlMultiHandle $multiHandle, EntitySearchResult $notificationForwards): array
    {
        $forwardRequests = [];

        /** @var PayonePaymentNotificationForwardEntity $forward */
        foreach ($notificationForwards as $forward) {
            $id = $forward->getId();

            $target = $forward->getNotificationTarget();

            if ($target === null || empty($target->getUrl())) {
                continue;
            }

            $forwardRequests[$id] = curl_init();

            $content = unserialize($forward->getContent(), []);

            if (!\is_array($content)) {
                continue;
            }

            foreach ($content as $key => $value) {
                $content[$key] = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            }

            curl_setopt($forwardRequests[$id], \CURLOPT_URL, $target->getUrl());
            curl_setopt($forwardRequests[$id], \CURLOPT_HEADER, false);
            curl_setopt($forwardRequests[$id], \CURLOPT_POST, true);
            curl_setopt($forwardRequests[$id], \CURLOPT_RETURNTRANSFER, true);
            curl_setopt($forwardRequests[$id], \CURLOPT_TIMEOUT, 10);
            curl_setopt($forwardRequests[$id], \CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($forwardRequests[$id], \CURLOPT_POSTFIELDS, http_build_query($content));
            curl_setopt($forwardRequests[$id], \CURLOPT_FAILONERROR, true);
            curl_setopt($forwardRequests[$id], \CURLOPT_HTTPHEADER, $this->buildHeaders($forward, $target));

            curl_multi_add_handle($multiHandle, $forwardRequests[$id]);
        }

        return $forwardRequests;
    }

    private function buildHeaders(
        PayonePaymentNotificationForwardEntity $forward,
        PayonePaymentNotificationTargetEntity  $target
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

    private function statusLogger(array $responseInfo, ?string $responseContent, string $id): void
    {
        $statusCode = $responseInfo['http_code'];

        $response = new Response($responseContent, $statusCode, $responseInfo);
        $logLevel = $response->isSuccessful() ? 'info' : 'error';
        $statusText = Response::$statusTexts[$statusCode] ?? 'Unknown Status Code';

        $this->logger->addRecord(
            Level::fromName($logLevel),
            'Forwarding notification - ' . $statusText,
            [
                'id' => $id,
                'information' => $responseInfo,
                'content' => $response->getContent(),
            ]
        );
    }
}
