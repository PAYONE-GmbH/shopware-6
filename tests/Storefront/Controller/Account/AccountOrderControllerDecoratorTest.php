<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Account;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\AccountOrderController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AccountOrderControllerDecoratorTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItDecoratesCorrectly(): void
    {
        $controller = $this->getContainer()->get(AccountOrderController::class);

        static::assertInstanceOf(AccountOrderControllerDecorator::class, $controller);
    }

    public function testItRedirectsFromEditOrderPageToOverviewPageOnRatepayOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction  = $this->getPaymentTransaction($order, PayoneRatepayDebitPaymentHandler::class);
        $order->setTransactions(new OrderTransactionCollection([$paymentTransaction->getOrderTransaction()]));

        $orderRepository = $this->createMock(EntityRepositoryInterface::class);
        $orderRepository->expects($this->once())->method('search')->willReturn(
            $this->getEntitySearchResult($order, $salesChannelContext)
        );

        $decoratedController = $this->createMock(AccountOrderController::class);
        $decoratedController->expects($this->never())->method('editOrder');

        $controller = new AccountOrderControllerDecorator(
            $decoratedController,
            $orderRepository
        );
        $controller->setContainer($this->getContainer());

        $response = $controller->editOrder($order->getId(), new Request(), $salesChannelContext);

        static::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testItNotRedirectsFromEditOrderPageToOverviewPageOnOtherPaymentMethodThanRatepay(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction  = $this->getPaymentTransaction($order, PayoneDebitPaymentHandler::class);
        $order->setTransactions(new OrderTransactionCollection([$paymentTransaction->getOrderTransaction()]));

        $orderRepository = $this->createMock(EntityRepositoryInterface::class);
        $orderRepository->expects($this->once())->method('search')->willReturn(
            $this->getEntitySearchResult($order, $salesChannelContext)
        );

        $request             = new Request();
        $decoratedController = $this->createMock(AccountOrderController::class);
        $decoratedController->expects($this->once())->method('editOrder')->with(
            $this->equalTo($order->getId()),
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );

        $controller = new AccountOrderControllerDecorator(
            $decoratedController,
            $orderRepository
        );
        $controller->setContainer($this->getContainer());

        $response = $controller->editOrder($order->getId(), $request, $salesChannelContext);

        static::assertNotInstanceOf(RedirectResponse::class, $response);
    }

    public function testItRedirectsFromUpdateOrderRequestToOverviewPageOnRatepayOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction  = $this->getPaymentTransaction($order, PayoneRatepayDebitPaymentHandler::class);
        $order->setTransactions(new OrderTransactionCollection([$paymentTransaction->getOrderTransaction()]));

        $orderRepository = $this->createMock(EntityRepositoryInterface::class);
        $orderRepository->expects($this->once())->method('search')->willReturn(
            $this->getEntitySearchResult($order, $salesChannelContext)
        );

        $decoratedController = $this->createMock(AccountOrderController::class);
        $decoratedController->expects($this->never())->method('updateOrder');

        $controller = new AccountOrderControllerDecorator(
            $decoratedController,
            $orderRepository
        );
        $controller->setContainer($this->getContainer());

        $response = $controller->updateOrder($order->getId(), new Request(), $salesChannelContext);

        static::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testItNotRedirectsFromUpdateOrderRequestToOverviewPageOnOtherPaymentMethodThanRatepay(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction  = $this->getPaymentTransaction($order, PayoneDebitPaymentHandler::class);
        $order->setTransactions(new OrderTransactionCollection([$paymentTransaction->getOrderTransaction()]));

        $orderRepository = $this->createMock(EntityRepositoryInterface::class);
        $orderRepository->expects($this->once())->method('search')->willReturn(
            $this->getEntitySearchResult($order, $salesChannelContext)
        );

        $request             = new Request();
        $decoratedController = $this->createMock(AccountOrderController::class);
        $decoratedController->expects($this->once())->method('updateOrder')->with(
            $this->equalTo($order->getId()),
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );

        $controller = new AccountOrderControllerDecorator(
            $decoratedController,
            $orderRepository
        );
        $controller->setContainer($this->getContainer());

        $response = $controller->updateOrder($order->getId(), $request, $salesChannelContext);

        static::assertNotInstanceOf(RedirectResponse::class, $response);
    }

    public function testItCallsParentFunctions(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $request             = new Request();
        $orderId             = Uuid::randomHex();

        $decoratedController = $this->createMock(AccountOrderController::class);
        $decoratedController->expects($this->once())->method('orderOverview')->with(
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );
        $decoratedController->expects($this->once())->method('cancelOrder')->with(
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );
        $decoratedController->expects($this->once())->method('orderSingleOverview')->with(
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );
        $decoratedController->expects($this->once())->method('ajaxOrderDetail')->with(
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );
        $decoratedController->expects($this->once())->method('orderChangePayment')->with(
            $this->equalTo($orderId),
            $this->equalTo($request),
            $this->equalTo($salesChannelContext)
        );

        $controller = new AccountOrderControllerDecorator(
            $decoratedController,
            $this->createMock(EntityRepositoryInterface::class)
        );

        $controller->orderOverview($request, $salesChannelContext);
        $controller->cancelOrder($request, $salesChannelContext);
        $controller->orderSingleOverview($request, $salesChannelContext);
        $controller->ajaxOrderDetail($request, $salesChannelContext);
        $controller->orderChangePayment($orderId, $request, $salesChannelContext);
    }

    protected function getEntitySearchResult(OrderEntity $order, SalesChannelContext $salesChannelContext): EntitySearchResult
    {
        return new EntitySearchResult(
            'order',
            1,
            new EntityCollection([$order]),
            null,
            new Criteria(),
            $salesChannelContext->getContext()
        );
    }
}
