<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Provider\Klarna\Service\KlarnaSessionService;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class CreateSessionRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private RequestBuilderServiceAccessor $serviceAccessor,
        private CartService $cartService,
        private OrderLoaderService $orderLoaderService,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $salesChannelContext = $arguments->salesChannelContext;

        if (KlarnaSessionService::EMPTY_ORDER_ID === $arguments->paymentTransaction->order->getId()) {
            $cart        = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $totalAmount = $cart->getPrice()->getTotalPrice();
            $currency    = $salesChannelContext->getCurrency();
            $lineItems   = $this->serviceAccessor->lineItemHydrator->mapCartLines($cart, $salesChannelContext);
        } else {
            $context = $salesChannelContext->getContext();

            // make sure, all required associations are loaded
            $order = $this->orderLoaderService->getOrderById($arguments->paymentTransaction->order->getId(), $context);

            $totalAmount = $order->getPrice()->getTotalPrice();
            $currency    = $this->orderLoaderService->getOrderCurrency($order, $context);
            $lineItems   = $this->serviceAccessor->lineItemHydrator->mapOrderLines($currency, $order, $context);
        }

        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $totalAmount,
            $salesChannelContext->getCurrency(),
        );

        $parameters = [
            'request'             => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]' => 'start_session',
            'clearingtype'        => PayoneClearingEnum::FINANCING->value,
            'amount'              => $amount,
            'currency'            => $currency->getIsoCode(),
        ];

        return \array_merge($parameters, $lineItems);
    }
}
