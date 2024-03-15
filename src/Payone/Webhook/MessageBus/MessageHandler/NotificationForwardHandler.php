<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\MessageBus\MessageHandler;

use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardMessage;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: NotificationForwardMessage::class)]
class NotificationForwardHandler
{
    public function __construct(
        private readonly EntityRepository $forwardTargetRepository,
        private readonly EntityRepository $notificationForwardRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotificationForwardMessage $message): void
    {
        $target = $this->forwardTargetRepository->search(new Criteria([$message->getNotificationTargetId()]), Context::createDefaultContext())->first();
        if (!$target instanceof PayonePaymentNotificationTargetEntity) {
            // should never occur - just to be safe.
            return;
        }

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $target->getUrl());
        curl_setopt($ch, \CURLOPT_HEADER, false);
        curl_setopt($ch, \CURLOPT_POST, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, \CURLOPT_POSTFIELDS, http_build_query($message->getRequestData()));
        curl_setopt($ch, \CURLOPT_FAILONERROR, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $this->buildHeaders($message, $target));

        $responseContent = (string)curl_exec($ch);
        $responseInfo = curl_getinfo($ch);
        curl_close($ch);

        $this->statusLogger($responseInfo, $responseContent, $message);
        $this->saveNotificationForward($responseInfo, $responseContent, $message);
    }

    public static function getHandledMessages(): iterable
    {
        return [
            NotificationForwardMessage::class,
        ];
    }

    private function buildHeaders(
        NotificationForwardMessage $message,
        PayonePaymentNotificationTargetEntity $target
    ): array {
        $headers = [
            'X-Forwarded-For: ' . $message->getClientIp(),
        ];

        if ($target->isBasicAuth() === true) {
            $headers[] = 'Content-Type:application/json';
            $headers[] = 'Authorization: Basic ' . base64_encode($target->getUsername() . ':' . $target->getPassword());
        }

        return $headers;
    }

    private function statusLogger(array $responseInfo, string $responseContent, NotificationForwardMessage $message): void
    {
        $response = new Response($responseContent, $responseInfo['http_code'], $responseInfo);
        $logLevel = $response->isSuccessful() ? 'info' : 'error';

        $logContext = [
            'message' => [
                'target-id' => $message->getNotificationTargetId(),
                'transaction-id' => $message->getPaymentTransactionId(),
                'request-data' => $message->getRequestData(),
                'client-ip' => $message->getClientIp(),
            ],
            'response' => [
                'status' => $responseInfo['http_code'],
                'content' => $responseContent,
            ],
        ];

        $this->logger->{$logLevel}('Notification has been forwarded', $logContext);
    }

    private function saveNotificationForward(string $responseContent, NotificationForwardMessage $message): void
    {
        $this->notificationForwardRepository->upsert([[
            'id' => Uuid::randomHex(),
            'notificationTargetId' => $message->getNotificationTargetId(),
            'ip' => $message->getClientIp(),
            'txaction' => $message->getRequestData()['txaction'] ?? '',
            'response' => $responseContent,
            'transactionId' => $message->getPaymentTransactionId(),
            'content' => json_encode($message->getRequestData()),
        ]], Context::createDefaultContext());
    }
}
