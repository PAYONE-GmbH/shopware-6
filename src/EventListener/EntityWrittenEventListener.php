<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Service\ActivePaymentMethodsLoaderService;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class EntityWrittenEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ActivePaymentMethodsLoaderService $activePaymentMethodsLoader,
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onEntityWritten',
        ];
    }

    public function onEntityWritten(EntityWrittenContainerEvent $event): void
    {
        $ids = [];

        $paymentMethodEvents = $event->getEventByEntityName(PaymentMethodDefinition::ENTITY_NAME);

        if (null !== $paymentMethodEvents) {
            $ids = \array_merge($ids, $this->collectPrimaryKeys($paymentMethodEvents->getIds()));
        }

        $salesChannelEvents = $event->getEventByEntityName(SalesChannelPaymentMethodDefinition::ENTITY_NAME);

        if (null !== $salesChannelEvents) {
            $ids = \array_merge($ids, $this->collectPrimaryKeys($salesChannelEvents->getIds()));
        }

        if ([] === $ids) {
            return;
        }

        /** @var PaymentMethodInterface $payonePaymentMethod */
        foreach ($ids as $id) {
            if (!$this->paymentMethodRegistry->hasId($id)) {
                continue;
            }

            $this->activePaymentMethodsLoader->clearCache($event->getContext());

            return;
        }
    }

    private function collectPrimaryKeys(array $primaryKeys): array
    {
        $ids = [];

        foreach ($primaryKeys as $key) {
            if (\is_array($key)) {
                $ids = [ ...$ids, ...\array_values($key) ];

                continue;
            }

            $ids[] = $key;
        }

        return $ids;
    }
}
