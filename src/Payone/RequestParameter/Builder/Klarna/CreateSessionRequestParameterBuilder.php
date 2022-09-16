<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

class CreateSessionRequestParameterBuilder extends AbstractKlarnaParameterBuilder
{
    /** @var CartService */
    private $cartService;
    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;
    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;
    /** @var OrderFetcherInterface */
    private $orderFetcher;

    public function __construct(
        CartService $cartService,
        LineItemHydratorInterface $lineItemHydrator,
        CurrencyPrecisionInterface $currencyPrecision,
        OrderFetcherInterface $orderFetcher
    ) {
        $this->cartService       = $cartService;
        $this->lineItemHydrator  = $lineItemHydrator;
        $this->currencyPrecision = $currencyPrecision;
        $this->orderFetcher      = $orderFetcher;
    }

    /**
     * @param KlarnaCreateSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();

        if ($order = $arguments->getOrderEntity()) {
            $context      = $salesChannelContext->getContext();
            $order        = $this->orderFetcher->getOrderById($order->getId(), $salesChannelContext->getContext()); // make sure, all required associations are loaded
            $totalAmount  = $order->getPrice()->getTotalPrice();
            $currencyCode = $order->getCurrency()->getIsoCode();
            $lineItems    = $this->lineItemHydrator->mapOrderLines($order->getCurrency(), $order, $context);
        } else {
            $cart         = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $totalAmount  = $cart->getPrice()->getTotalPrice();
            $currencyCode = $salesChannelContext->getCurrency()->getIsoCode();
            $lineItems    = $this->lineItemHydrator->mapCartLines($cart, $salesChannelContext);
        }

        $parameters = [
            'request'             => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'start_session',
            'clearingtype'        => self::CLEARING_TYPE_FINANCING,
            'amount'              => $this->currencyPrecision->getRoundedTotalAmount($totalAmount, $salesChannelContext->getCurrency()),
            'currency'            => $currencyCode,
        ];

        return array_merge($parameters, $lineItems);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof KlarnaCreateSessionStruct;
    }
}
