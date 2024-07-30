<?php
declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\TestCaseBase\Mock\PaymentHandler\PaymentHandlerMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FilteredPaymentMethodRouteTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfMethodsGotFiltered(): void
    {
        $ids = [Uuid::randomHex(), Uuid::randomHex()];
        $decorated = $this->createMock(AbstractPaymentMethodRoute::class);
        $decorated->method('load')->willReturn($this->getDefaultResponse($ids, true));

        $filter = $this->getFilterService($ids);

        $route = new FilteredPaymentMethodRoute(
            $decorated,
            $filter,
            new RequestStack(),
            $this->createMock(OrderFetcherInterface::class),
            $this->getContainer()->get(CartService::class),
            $this->getContainer()->get(PaymentFilterContextFactoryInterface::class)
        );

        $response = $route->load(new Request(), $this->createSalesChannelContext(), new Criteria());
        static::assertEquals(1, $response->getPaymentMethods()->count());
        static::assertEquals(stdClass::class, $response->getPaymentMethods()->first()->getHandlerIdentifier());
    }

    public function testIfServiceWontCrashOnEmptyMethods(): void
    {
        $ids = [Uuid::randomHex(), Uuid::randomHex()];
        $decorated = $this->createMock(AbstractPaymentMethodRoute::class);
        $decorated->method('load')->willReturn($this->getDefaultResponse([], false));

        $filter = $this->getFilterService($ids);

        $route = new FilteredPaymentMethodRoute(
            $decorated,
            $filter,
            new RequestStack(),
            $this->createMock(OrderFetcherInterface::class),
            $this->getContainer()->get(CartService::class),
            $this->getContainer()->get(PaymentFilterContextFactoryInterface::class)
        );

        $response = $route->load(new Request(), $this->createSalesChannelContext(), new Criteria());
        static::assertEquals(0, $response->getPaymentMethods()->count());
    }

    private function getFilterService(array $ids): IterablePaymentFilter
    {
        return new class($ids) extends IterablePaymentFilter {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             */
            public function __construct(
                private readonly array $idsToRemove
            ) {
            }

            public function filterPaymentMethods(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
            {
                foreach ($this->idsToRemove as $id) {
                    $methodCollection->remove($id);
                }
            }
        };
    }

    private function getDefaultResponse(array $payoneMethodIds, bool $addStd): PaymentMethodRouteResponse
    {
        $methods = [];
        foreach ($payoneMethodIds as $id) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId($id);
            $paymentMethod->setHandlerIdentifier(PaymentHandlerMock::class);
            $methods[] = $paymentMethod;
        }
        if ($addStd) {
            $paymentMethod2 = new PaymentMethodEntity();
            $paymentMethod2->setId(Uuid::randomHex());
            $paymentMethod2->setHandlerIdentifier(stdClass::class);
            $methods[] = $paymentMethod2;
        }

        $entitySearchResult = new EntitySearchResult(
            'payment_method',
            \count($methods),
            new PaymentMethodCollection($methods),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        return new PaymentMethodRouteResponse($entitySearchResult);
    }
}
