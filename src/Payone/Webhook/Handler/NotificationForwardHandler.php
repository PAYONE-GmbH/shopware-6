<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetCollection;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationForwardHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly EntityRepository $notificationTargetRepository,
        private readonly EntityRepository $notificationForwardRepository,
        private readonly TransactionDataHandlerInterface $transactionDataHandler,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (\array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $paymentTransactionId = $this->getPaymentTransactionId($request->request->getInt('txid'), $salesChannelContext);

        if ($paymentTransactionId === null) {
            return;
        }

        $notificationTargets = $this->getRelevantNotificationTargets($request->request->getAlnum('txaction'), $salesChannelContext);

        if ($notificationTargets === null) {
            return;
        }

        $notificationForwards = $this->persistNotificationForwards($notificationTargets, $request, $paymentTransactionId, $salesChannelContext);

        $this->messageBus->dispatch(new NotificationForwardCommand(array_column($notificationForwards, 'id'), $salesChannelContext->getContext()));
    }

    private function persistNotificationForwards(
        PayonePaymentNotificationTargetCollection $notificationTargets,
        Request $request,
        string $paymentTransactionId,
        SalesChannelContext $salesChannelContext
    ): array {
        /** @var array $data this annotation is required for rector */
        $data = $request->request->all();
        $notificationForwards = [];

        foreach ($notificationTargets as $target) {
            $notificationForwards[] = [
                'id' => Uuid::randomHex(),
                'content' => serialize(mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1')),
                'notificationTargetId' => $target->getId(),
                'transactionId' => $paymentTransactionId,
                'ip' => $request->getClientIp(),
                'txaction' => $data['txaction'],
            ];
        }

        $this->notificationForwardRepository->upsert($notificationForwards, $salesChannelContext->getContext());

        return $notificationForwards;
    }

    private function getRelevantNotificationTargets(string $txaction, SalesChannelContext $salesChannelContext): ?PayonePaymentNotificationTargetCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new ContainsFilter('txactions', $txaction)
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
            $txid
        );

        return $paymentTransaction?->getOrderTransaction()->getId();
    }
}
