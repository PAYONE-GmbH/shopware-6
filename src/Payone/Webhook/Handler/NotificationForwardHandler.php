<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\DataAbstractionLayer\Entity\NotificationTarget\PayonePaymentNotificationTargetEntity;
use PayonePayment\Payone\Webhook\MessageBus\Command\NotificationForwardCommand;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationForwardHandler implements WebhookHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $notificationTargetRepository;

    /** @var EntityRepositoryInterface */
    private $notificationForwardRepository;

    /** @var TransactionDataHandlerInterface */
    private $transactionDataHandler;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(
        EntityRepositoryInterface $notificationTargetRepository,
        EntityRepositoryInterface $notificationForwardRepository,
        TransactionDataHandlerInterface $transactionDataHandler,
        MessageBusInterface $messageBus
    ) {
        $this->notificationTargetRepository  = $notificationTargetRepository;
        $this->notificationForwardRepository = $notificationForwardRepository;
        $this->transactionDataHandler        = $transactionDataHandler;
        $this->messageBus                    = $messageBus;
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        if (array_key_exists('txaction', $data)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SalesChannelContext $salesChannelContext, array $data): void
    {
        $paymentTransactionId = $this->getPaymentTransactionId((int) $data['txid'], $salesChannelContext);
        $notificationTargets  = $this->getRelevantNotificationTargets($data['txaction'], $salesChannelContext);

        if (null === $notificationTargets) {
            return;
        }

        $notificationForwards = $this->persistNotificationForwards($notificationTargets, $data, $paymentTransactionId, $salesChannelContext);

        $this->messageBus->dispatch(new NotificationForwardCommand(array_column($notificationForwards, 'id'), $salesChannelContext->getContext()));
    }

    private function persistNotificationForwards(EntityCollection $notificationTargets, array $data, ?string $paymentTransactionId, SalesChannelContext $salesChannelContext): array
    {
        $notificationForwards = [];

        foreach ($notificationTargets as $target) {
            /** @var PayonePaymentNotificationTargetEntity $target */
            $notificationForwards[] = [
                'id'                   => Uuid::randomHex(),
                'content'              => serialize(mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1')),
                'notificationTargetId' => $target->getId(),
                'transactionId'        => $paymentTransactionId,
                'txaction'             => $data['txaction'],
            ];
        }

        $this->notificationForwardRepository->upsert($notificationForwards, $salesChannelContext->getContext());

        return $notificationForwards;
    }

    private function getRelevantNotificationTargets(string $txaction, SalesChannelContext $salesChannelContext): ?EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new ContainsFilter('txactions', $txaction)
        );

        $notificationTargets = $this->notificationTargetRepository->search($criteria, $salesChannelContext->getContext());

        if ($notificationTargets->count() <= 0) {
            return null;
        }

        return $notificationTargets->getEntities();
    }

    private function getPaymentTransactionId(int $txid, SalesChannelContext $salesChannelContext): ?string
    {
        /** @var null|PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            $txid
        );

        if (null === $paymentTransaction) {
            return null;
        }

        return $paymentTransaction->getOrderTransaction()->getId();
    }
}
