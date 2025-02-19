<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @covers \PayonePayment\Components\PaymentFilter\TotalPriceFilterTest
 */
class TotalPriceFilterTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfRemovesAllMethodsIfPriceEqualZeroForCart(): void
    {
        $filter = new TotalPriceFilter();

        $context = new PaymentFilterContext(
            $this->createMock(SalesChannelContext::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CurrencyEntity::class),
            null,
            $cartMock = $this->createMock(Cart::class)
        );
        $cartMock->method('getLineItems')->willReturn(new LineItemCollection([$this->createMock(LineItem::class)]));

        $cartMock->method('getPrice')->willReturn($this->createPrice(0));
        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(3, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(0, PayonePrepaymentPaymentHandler::class, $collection);

        $cartMock->method('getPrice')->willReturn($this->createPrice(-100));
        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(3, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(0, PayonePrepaymentPaymentHandler::class, $collection);
    }

    public function testIfRemovesAllMethodsIfPriceEqualZeroForOrder(): void
    {
        $filter = new TotalPriceFilter();

        $context = new PaymentFilterContext(
            $this->createMock(SalesChannelContext::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CurrencyEntity::class),
            null,
            $cartMock = $this->createMock(Cart::class)
        );
        $cartMock->method('getLineItems')->willReturn(new LineItemCollection([$this->createMock(LineItem::class)]));

        $cartMock->method('getPrice')->willReturn($this->createPrice(0));
        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(3, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(0, PayonePrepaymentPaymentHandler::class, $collection);

        $cartMock->method('getPrice')->willReturn($this->createPrice(-100));
        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(3, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(0, PayonePrepaymentPaymentHandler::class, $collection);
    }

    public function testIfKeepsAllMethodsIfPriceHigherZeroForCart(): void
    {
        $filter = new TotalPriceFilter();

        $context = new PaymentFilterContext(
            $this->createMock(SalesChannelContext::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CurrencyEntity::class),
            $orderMock = $this->createMock(OrderEntity::class),
        );

        $orderMock->method('getPrice')->willReturn($this->createPrice(100));

        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(6, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(3, PayonePrepaymentPaymentHandler::class, $collection);
    }

    public function testIfRemovesAllMethodsIfPriceHigherZeroForOrder(): void
    {
        $filter = new TotalPriceFilter();

        $context = new PaymentFilterContext(
            $this->createMock(SalesChannelContext::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CustomerAddressEntity::class),
            $this->createMock(CurrencyEntity::class),
            $orderMock = $this->createMock(OrderEntity::class),
        );

        $orderMock->method('getPrice')->willReturn($this->createPrice(100));

        $filter->filterPaymentMethods($collection = $this->getMethodCollection(), $context);
        static::assertCount(6, $collection->getElements());
        static::assertPaymentMethodCount(3, DefaultPayment::class, $collection);
        static::assertPaymentMethodCount(3, PayonePrepaymentPaymentHandler::class, $collection);
    }

    private static function assertPaymentMethodCount(int $expectedCount, string $paymentHandlerClass, PaymentMethodCollection $collection): void
    {
        static::assertCount($expectedCount, $collection->filter(static fn (PaymentMethodEntity $e) => $e->getHandlerIdentifier() === $paymentHandlerClass)->getElements(), sprintf('there should be %s payment methods with %s handler', $expectedCount, $paymentHandlerClass));
    }

    private function getMethodCollection(): PaymentMethodCollection
    {
        return new PaymentMethodCollection([
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => DefaultPayment::class]),
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => DefaultPayment::class]),
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => DefaultPayment::class]),
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => PayonePrepaymentPaymentHandler::class]),
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => PayonePrepaymentPaymentHandler::class]),
            (new PaymentMethodEntity())->assign(['id' => Uuid::randomHex(), 'handlerIdentifier' => PayonePrepaymentPaymentHandler::class]),
        ]);
    }
}
