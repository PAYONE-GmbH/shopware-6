<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ActivePaymentMethodsLoader implements ActivePaymentMethodsLoaderInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
        private readonly SalesChannelRepository $paymentMethodRepository,
        private readonly EntityRepository $salesChannelRepository
    ) {
    }

    public function getActivePaymentMethodIds(SalesChannelContext $salesChannelContext): array
    {
        $cacheKey = $this->generateCacheKey($salesChannelContext->getSalesChannelId());

        $cacheItem = $this->cachePool->getItem($cacheKey);

        if ($cacheItem->get() === null) {
            $cacheItem->set($this->collectActivePayonePaymentMethodIds($salesChannelContext));

            $this->cachePool->save($cacheItem);
        }

        return $cacheItem->get();
    }

    public function clearCache(Context $context): void
    {
        $cacheKeys = [];

        /** @var string[] $salesChannelIds */
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();

        foreach ($salesChannelIds as $salesChannelId) {
            $cacheKeys[] = $this->generateCacheKey($salesChannelId);
        }

        if ($cacheKeys === []) {
            return;
        }

        $this->cachePool->deleteItems($cacheKeys);
    }

    private function collectActivePayonePaymentMethodIds(SalesChannelContext $salesChannelContext): array
    {
        $criteria = new Criteria();

        $criteria->addFilter(new ContainsFilter('handlerIdentifier', 'PayonePayment'));
        $criteria->addFilter(new EqualsFilter('active', true));

        return $this->paymentMethodRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }

    private function generateCacheKey(string $salesChannelId): string
    {
        return 'payone_payment.active_payment_methods.' . $salesChannelId;
    }
}
