<?php declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class FilteredPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    private AbstractPaymentMethodRoute $decorated;

    private IterablePaymentFilter $iterablePaymentFilter;

    private RequestStack $requestStack;

    private OrderFetcherInterface $orderFetcher;

    private CartService $cartService;

    private PaymentFilterContextFactoryInterface $paymentFilterContextFactory;

    public function __construct(
        AbstractPaymentMethodRoute $decorated,
        IterablePaymentFilter $iterablePaymentFilter,
        RequestStack $requestStack,
        OrderFetcherInterface $orderFetcher,
        CartService $cartService,
        PaymentFilterContextFactoryInterface $paymentFilterContextFactory
    ) {
        $this->decorated = $decorated;
        $this->iterablePaymentFilter = $iterablePaymentFilter;
        $this->requestStack = $requestStack;
        $this->orderFetcher = $orderFetcher;
        $this->cartService = $cartService;
        $this->paymentFilterContextFactory = $paymentFilterContextFactory;
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    /**
     * This decoration filters out PAYONE payment methods if necessary. The PayonePaymentMethodValidator takes care
     * of changing the currently selected payment method if it is no longer available.
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $response = $this->getDecorated()->load($request, $context, $criteria);

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            return $response;
        }

        if ($request->query->getBoolean('onlyAvailable') || $request->request->getBoolean('onlyAvailable')) {
            $orderId = $currentRequest->get('orderId');
            if ($orderId) {
                $order = $this->orderFetcher->getOrderById($orderId, $context->getContext());
                if (!$order) {
                    throw new \RuntimeException('order not found!');
                }
                $filterContext = $this->paymentFilterContextFactory->createContextForOrder($order, $context);
            } else {
                $filterContext = $this->paymentFilterContextFactory->createContextForCart(
                    $this->cartService->getCart($context->getToken(), $context),
                    $context
                );
            }

            $paymentMethods = $response->getPaymentMethods();

            $paymentMethods = $this->iterablePaymentFilter->filterPaymentMethods($paymentMethods, $filterContext);

            $criteria->setIds($paymentMethods->getIds());

            return $this->getDecorated()->load($request, $context, $criteria);
        }

        return $response;
    }
}
