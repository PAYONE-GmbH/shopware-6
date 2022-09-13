<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CreateSessionRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    use FinancingTypeTrait;

    private CartService $cartService;
    private LineItemHydratorInterface $lineItemHydrator;
    private CurrencyPrecisionInterface $currencyPrecision;
    private EntityRepository $orderRepository;

    public function __construct(
        CartService $cartService,
        LineItemHydratorInterface $lineItemHydrator,
        CurrencyPrecisionInterface $currencyPrecision,
        EntityRepository $orderRepository
    ) {
        $this->cartService       = $cartService;
        $this->lineItemHydrator  = $lineItemHydrator;
        $this->currencyPrecision = $currencyPrecision;
        $this->orderRepository   = $orderRepository;
    }

    /**
     * @param KlarnaCreateSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();

        if ($order = $arguments->getOrderEntity()) {
            $context             = $salesChannelContext->getContext();
            $orderSearchCriteria = (new Criteria([$order->getId()]))
                ->addAssociation('currency')
                ->addAssociation('lineItems')
                ->addAssociation('deliveries');
            $order        = $this->orderRepository->search($orderSearchCriteria, $context)->first();
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
            'financingtype'       => $this->getFinancingType($arguments->getPaymentMethod()),
            'amount'              => $this->currencyPrecision->getRoundedTotalAmount($totalAmount, $salesChannelContext->getCurrency()),
            'currency'            => $currencyCode,
        ];

        return array_merge($parameters, $lineItems);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof KlarnaCreateSessionStruct;
    }
}
