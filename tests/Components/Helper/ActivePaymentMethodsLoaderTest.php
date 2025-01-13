<?php

declare(strict_types=1);

namespace PayonePayment\Components\Helper;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @covers \PayonePayment\Components\Helper\ActivePaymentMethodsLoader
 */
class ActivePaymentMethodsLoaderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsCorrectPaymentMethodIdsWithoutExistingCacheItem(): void
    {
        $activePaymentMethodIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(static::exactly(2))->method('get')->willReturnOnConsecutiveCalls(null, $activePaymentMethodIds);
        $cacheItem->expects(static::once())->method('set')->with(static::equalTo($activePaymentMethodIds));

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool
            ->expects(static::once())
            ->method('getItem')
            ->with(
                static::equalTo('payone_payment.active_payment_methods.' . $salesChannelContext->getSalesChannelId())
            )
            ->willReturn($cacheItem)
        ;
        $cachePool
            ->expects(static::once())
            ->method('save')
            ->with(
                static::equalTo($cacheItem)
            )
        ;

        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult
            ->expects(static::once())
            ->method('getIds')
            ->willReturn($activePaymentMethodIds)
        ;

        $paymentMethodRepository = $this->createMock(SalesChannelRepository::class);
        $paymentMethodRepository
            ->expects(static::once())
            ->method('searchIds')
            ->with(
                static::callback(static function (Criteria $criteria) {
                    $hasHandlerIdentifierFilter = false;
                    $hasActiveFilter = false;

                    foreach ($criteria->getFilters() as $filter) {
                        if ($filter instanceof ContainsFilter && $filter->getField() === 'handlerIdentifier' && $filter->getValue() === 'PayonePayment') {
                            $hasHandlerIdentifierFilter = true;
                        }
                        if ($filter instanceof EqualsFilter && $filter->getField() === 'active' && $filter->getValue() === true) {
                            $hasActiveFilter = true;
                        }
                    }

                    static::assertTrue($hasHandlerIdentifierFilter);
                    static::assertTrue($hasActiveFilter);

                    return true;
                }),
                static::isInstanceOf(SalesChannelContext::class)
            )
            ->willReturn($idSearchResult)
        ;

        $salesChannelRepository = $this->createMock(EntityRepository::class);

        $activePaymentMethodsLoader = new ActivePaymentMethodsLoader(
            $cachePool,
            $paymentMethodRepository,
            $salesChannelRepository
        );

        $result = $activePaymentMethodsLoader->getActivePaymentMethodIds($salesChannelContext);

        static::assertSame($activePaymentMethodIds, $result);
    }

    public function testItReturnsCorrectPaymentMethodIdsWithExistingCacheItem(): void
    {
        $activePaymentMethodIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(static::exactly(2))->method('get')->willReturn($activePaymentMethodIds);
        $cacheItem->expects(static::never())->method('set');

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool
            ->expects(static::once())
            ->method('getItem')
            ->with(
                static::equalTo('payone_payment.active_payment_methods.' . $salesChannelContext->getSalesChannelId())
            )
            ->willReturn($cacheItem)
        ;
        $cachePool
            ->expects(static::never())
            ->method('save')
        ;

        $paymentMethodRepository = $this->createMock(SalesChannelRepository::class);
        $paymentMethodRepository
            ->expects(static::never())
            ->method('searchIds')
        ;

        $salesChannelRepository = $this->createMock(EntityRepository::class);

        $activePaymentMethodsLoader = new ActivePaymentMethodsLoader(
            $cachePool,
            $paymentMethodRepository,
            $salesChannelRepository
        );

        $result = $activePaymentMethodsLoader->getActivePaymentMethodIds($salesChannelContext);

        static::assertSame($activePaymentMethodIds, $result);
    }

    public function testItClearsCacheItems(): void
    {
        $salesChannelIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool
            ->expects(static::once())
            ->method('deleteItems')
            ->with(
                static::equalTo([
                    'payone_payment.active_payment_methods.' . $salesChannelIds[0],
                    'payone_payment.active_payment_methods.' . $salesChannelIds[1],
                ])
            )
        ;

        $paymentMethodRepository = $this->createMock(SalesChannelRepository::class);

        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult
            ->expects(static::once())
            ->method('getIds')
            ->willReturn($salesChannelIds)
        ;

        $salesChannelRepository = $this->createMock(EntityRepository::class);
        $salesChannelRepository
            ->expects(static::once())
            ->method('searchIds')
            ->willReturn($idSearchResult)
        ;

        $activePaymentMethodsLoader = new ActivePaymentMethodsLoader(
            $cachePool,
            $paymentMethodRepository,
            $salesChannelRepository
        );

        $activePaymentMethodsLoader->clearCache($salesChannelContext->getContext());
    }
}
