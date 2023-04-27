<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class CreateSessionRequestParameterBuilder extends AbstractKlarnaParameterBuilder
{
    private readonly CartService $cartService;

    public function __construct(
        CartService $cartService,
        private readonly LineItemHydratorInterface $lineItemHydrator,
        private readonly CurrencyPrecisionInterface $currencyPrecision,
        private readonly OrderFetcherInterface $orderFetcher
    ) {
        $this->cartService = $cartService;
    }

    /**
     * @param KlarnaCreateSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();

        if ($order = $arguments->getOrderEntity()) {
            $context = $salesChannelContext->getContext();
            $order = $this->getOrder($order->getId(), $context); // make sure, all required associations are loaded
            $totalAmount = $order->getPrice()->getTotalPrice();
            $currency = $this->getOrderCurrency($order, $context);
            $lineItems = $this->lineItemHydrator->mapOrderLines($currency, $order, $context);
        } else {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $totalAmount = $cart->getPrice()->getTotalPrice();
            $currency = $salesChannelContext->getCurrency();
            $lineItems = $this->lineItemHydrator->mapCartLines($cart, $salesChannelContext);
        }

        $parameters = [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'start_session',
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'amount' => $this->currencyPrecision->getRoundedTotalAmount($totalAmount, $salesChannelContext->getCurrency()),
            'currency' => $currency->getIsoCode(),
        ];

        return array_merge($parameters, $lineItems);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof KlarnaCreateSessionStruct;
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        // Load order to make sure all associations are set
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if ($order === null) {
            throw new \RuntimeException('missing order');
        }

        return $order;
    }
}
