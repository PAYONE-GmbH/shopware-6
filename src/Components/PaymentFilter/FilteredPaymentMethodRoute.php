<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class FilteredPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    public function __construct(
        private readonly AbstractPaymentMethodRoute $decorated,
        private readonly IterablePaymentFilter $iterablePaymentFilter,
        private readonly RequestStack $requestStack,
        private readonly OrderLoaderService $orderLoaderService,
        private readonly CartService $cartService,
        private readonly PaymentFilterContextFactoryInterface $paymentFilterContextFactory,
    ) {
    }

    #[\Override]
    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    /**
     * This decoration filters out PAYONE payment methods if necessary. The PayonePaymentMethodValidator takes care
     * of changing the currently selected payment method if it is no longer available.
     */
    #[\Override]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $response       = $this->getDecorated()->load($request, $context, $criteria);
        $currentRequest = $this->requestStack->getCurrentRequest();
        $orderId        = $currentRequest?->get('orderId');

        if ($orderId) {
            $order = $this->orderLoaderService->getOrderById($orderId, $context->getContext());

            if (!$order) {
                throw new \RuntimeException('order not found!');
            }

            $filterContext = $this->paymentFilterContextFactory->createContextForOrder($order, $context);
        } else {
            $filterContext = $this->paymentFilterContextFactory->createContextForCart(
                $this->cartService->getCart($context->getToken(), $context),
                $context,
            );
        }

        $this->iterablePaymentFilter->filterPaymentMethods($response->getPaymentMethods(), $filterContext);

        return $response;
    }
}
