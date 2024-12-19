<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Helper\ActivePaymentMethodsLoaderInterface;
use PayonePayment\Installer\PaymentMethodInstaller;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontRenderEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActivePaymentMethodsLoaderInterface $activePaymentMethodsLoader,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
            EntityWrittenContainerEvent::class => 'onEntityWritten',
        ];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        $event->setParameter(
            'activePayonePaymentMethodIds',
            $this->activePaymentMethodsLoader->getActivePaymentMethodIds($event->getSalesChannelContext())
        );
    }

    public function onEntityWritten(EntityWrittenContainerEvent $event): void
    {
        $ids = [];

        $paymentMethodEvents = $event->getEventByEntityName(PaymentMethodDefinition::ENTITY_NAME);

        if ($paymentMethodEvents !== null) {
            $ids = array_merge($ids, $this->collectPrimaryKeys($paymentMethodEvents->getIds()));
        }

        $salesChannelEvents = $event->getEventByEntityName(SalesChannelPaymentMethodDefinition::ENTITY_NAME);

        if ($salesChannelEvents !== null) {
            $ids = array_merge($ids, $this->collectPrimaryKeys($salesChannelEvents->getIds()));
        }

        if (empty($ids)) {
            return;
        }

        $clearCache = false;

        foreach (PaymentMethodInstaller::PAYMENT_METHODS as $paymentMethod) {
            if (\in_array($paymentMethod::UUID, $ids, true)) {
                $clearCache = true;
            }
        }

        if ($clearCache) {
            $this->activePaymentMethodsLoader->clearCache($event->getContext());
        }
    }

    private function collectPrimaryKeys(array $primaryKeys): array
    {
        $ids = [];

        foreach ($primaryKeys as $key) {
            if (\is_array($key)) {
                $ids = [...$ids, ...array_values($key)];
            } else {
                $ids[] = $key;
            }
        }

        return $ids;
    }
}
