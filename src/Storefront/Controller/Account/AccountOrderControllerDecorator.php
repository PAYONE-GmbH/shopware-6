<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Account;

use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\AccountOrderController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope": {"storefront"}})
 * @RouteScope(scopes={"storefront"})
 */
class AccountOrderControllerDecorator extends StorefrontController
{
    /**
     * @var AccountOrderController
     */
    protected $decoratedController;

    protected EntityRepositoryInterface $orderRepository;

    public function __construct(StorefrontController $decoratedController, EntityRepositoryInterface $orderRepository)
    {
        /** @phpstan-ignore-next-line */
        $this->decoratedController = $decoratedController;
        $this->orderRepository = $orderRepository;
    }

    public function orderOverview(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->orderOverview($request, $context);
    }

    public function cancelOrder(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->cancelOrder($request, $context);
    }

    public function orderSingleOverview(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->orderSingleOverview($request, $context);
    }

    public function ajaxOrderDetail(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->ajaxOrderDetail($request, $context);
    }

    public function editOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        if ($this->isRatepayOrder($orderId, $context->getContext())) {
            return $this->redirectToRoute('frontend.account.order.page');
        }

        return $this->decoratedController->editOrder($orderId, $request, $context);
    }

    public function orderChangePayment(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->orderChangePayment($orderId, $request, $context);
    }

    public function updateOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        if ($this->isRatepayOrder($orderId, $context->getContext())) {
            return $this->redirectToRoute('frontend.account.order.page');
        }

        return $this->decoratedController->updateOrder($orderId, $request, $context);
    }

    protected function isRatepayOrder(string $orderId, Context $context): bool
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.paymentMethod');

        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order && $order->getTransactions()) {
            $transaction = $order->getTransactions()->last();

            if ($transaction
                && $transaction->getPaymentMethod()
                && \in_array($transaction->getPaymentMethod()->getHandlerIdentifier(), PaymentHandlerGroups::RATEPAY, true)) {
                return true;
            }
        }

        return false;
    }
}
