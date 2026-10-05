<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetCollection;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardMessage;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class NotificationForwardHandler implements WebhookHandlerInterface
{
    public function __construct(
        private EntityRepository $notificationTargetRepository,
        private TransactionDataHandler $transactionDataHandler,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (\array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $paymentTransactionId = $this->getPaymentTransactionId($request->request->getInt('txid'), $salesChannelContext);

        if (null === $paymentTransactionId) {
            return;
        }

        $notificationTargets = $this->getRelevantNotificationTargets($request->request->getAlnum('txaction'), $salesChannelContext);

        if (null === $notificationTargets) {
            return;
        }

        $requestData = $this->utf8EncodeRecursive($request->request->all());

        foreach ($notificationTargets as $target) {
            $message = new NotificationForwardMessage(
                $target->getId(),
                $requestData,
                $paymentTransactionId,
                (string) $request->getClientIp(),
            );

            $this->messageBus->dispatch($message);
        }
    }

    private function getRelevantNotificationTargets(string $txaction, SalesChannelContext $salesChannelContext): ?PayonePaymentNotificationTargetCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new ContainsFilter('txactions', $txaction),
        );

        $notificationTargets = $this->notificationTargetRepository->search($criteria, $salesChannelContext->getContext());

        if ($notificationTargets->count() <= 0) {
            return null;
        }

        $result = $notificationTargets->getEntities();

        if (!($result instanceof PayonePaymentNotificationTargetCollection)) {
            throw new \LogicException('invalid collection type ' . $result::class);
        }

        return $result;
    }

    private function getPaymentTransactionId(int $txid, SalesChannelContext $salesChannelContext): ?string
    {
        /** @var PaymentTransaction|null $paymentTransaction */
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            $txid,
        );

        return $paymentTransaction?->getOrderTransaction()->getId();
    }

    private function utf8EncodeRecursive(array $data): array
    {
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $value = $this->utf8EncodeRecursive($value);

                continue;
            }

            $value = mb_convert_encoding((string) $value, 'UTF-8', 'ISO-8859-1');
        }
        unset($value);

        return $data;
    }
}
