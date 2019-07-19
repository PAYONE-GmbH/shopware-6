<?php

namespace PayonePayment\EventListener;

use PayonePayment\Installer\CustomFieldInstaller;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontRenderEventListener implements EventSubscriberInterface
{
    private const CACHE_ID = 'payone_payment.menu_state';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var SalesChannelRepositoryInterface */
    private $repository;

    public function __construct(CacheItemPoolInterface $cache, SalesChannelRepositoryInterface $repository)
    {
        $this->cache      = $cache;
        $this->repository = $repository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
        ];
    }

    public function onRender(StorefrontRenderEvent $event)
    {
        $activePaymentMethods = $this->cache->getItem(self::CACHE_ID);

        if ($activePaymentMethods->get() === null) {
            $activePaymentMethods->set($this->collectActivePayonePaymentMethods($event->getSalesChannelContext()));

            $this->cache->save($activePaymentMethods);
        }

        $event->setParameter('activePaymentPaymentMethods', $activePaymentMethods->get());
    }

    private function collectActivePayonePaymentMethods(SalesChannelContext $context): array
    {
        $criteria = new Criteria();

        $field = sprintf('customFields.%s', CustomFieldInstaller::IS_PAYONE);
        $criteria->addFilter(new EqualsFilter($field, true));

        $criteria->addFilter(new EqualsFilter('active', true));

        return $this->repository->search($criteria, $context)->getIds();
    }
}
