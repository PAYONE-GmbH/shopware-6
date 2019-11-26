<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Installer\PaymentMethodInstaller;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontRenderEventListener implements EventSubscriberInterface
{
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var SalesChannelRepositoryInterface */
    private $paymentMethodRepository;

    /** @var EntityRepositoryInterface */
    private $salesChannelRepository;

    public function __construct(
        CacheItemPoolInterface $cachePool,
        SalesChannelRepositoryInterface $repository,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->cachePool               = $cachePool;
        $this->paymentMethodRepository = $repository;
        $this->salesChannelRepository  = $salesChannelRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class       => 'onRender',
            EntityWrittenContainerEvent::class => 'onEntityWritten',
        ];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        $cacheKey = $this->generateCacheKey(
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );

        $activePaymentMethods = $this->cachePool->getItem($cacheKey);

        if ($activePaymentMethods->get() === null) {
            $activePaymentMethods->set(
                $this->collectActivePayonePaymentMethods(
                    $event->getSalesChannelContext()
                )
            );

            $this->cachePool->save($activePaymentMethods);
        }

        $event->setParameter('activePaymentPaymentMethods', $activePaymentMethods->get());
    }

    public function onEntityWritten(EntityWrittenContainerEvent $event): void
    {
        $ids = [];

        $paymentMethodEvents = $event->getEventByEntityName(PaymentMethodDefinition::ENTITY_NAME);

        if (null !== $paymentMethodEvents) {
            $ids = array_merge($ids, $this->collectPrimaryKeys($paymentMethodEvents->getIds()));
        }

        $salesChannelEvents = $event->getEventByEntityName(SalesChannelPaymentMethodDefinition::ENTITY_NAME);

        if (null !== $salesChannelEvents) {
            $ids = array_merge($ids, $this->collectPrimaryKeys($salesChannelEvents->getIds()));
        }

        if (empty($ids)) {
            return;
        }

        $clearCache = false;

        foreach (PaymentMethodInstaller::PAYMENT_METHODS as $paymentMethod) {
            if (in_array($paymentMethod::UUID, $ids, true)) {
                $clearCache = true;
            }
        }

        if ($clearCache) {
            $this->clearCache($event->getContext());
        }
    }

    private function collectPrimaryKeys(array $primaryKeys): array
    {
        $ids = [];

        foreach ($primaryKeys as $key) {
            if (is_array($key)) {
                $ids = array_merge($ids, array_values($key));
            } else {
                $ids[] = $key;
            }
        }

        return $ids;
    }

    private function collectActivePayonePaymentMethods(SalesChannelContext $context): array
    {
        $criteria = new Criteria();

        $field = sprintf('customFields.%s', CustomFieldInstaller::IS_PAYONE);
        $criteria->addFilter(new EqualsFilter($field, true));

        $criteria->addFilter(new EqualsFilter('active', true));

        return $this->paymentMethodRepository->search($criteria, $context)->getIds();
    }

    private function generateCacheKey(string $salesChannel): string
    {
        return 'payone_payment.menu_state.' . $salesChannel;
    }

    private function clearCache(Context $context): void
    {
        $cacheKeys = [];

        /** @var string[] $salesChannels */
        $salesChannels = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();

        foreach ($salesChannels as $salesChannel) {
            $cacheKeys[] = $this->generateCacheKey($salesChannel);
        }

        if (empty($cacheKeys)) {
            return;
        }

        $this->cachePool->deleteItems($cacheKeys);
    }
}
