<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Exception\LogicException;

class CheckoutConfirmCartDataEventListener implements EventSubscriberInterface
{
    /** @var OrderConverter */
    private $orderConverter;

    /** @var OrderFetcherInterface */
    private $orderFetcher;

    public function __construct(
        OrderConverter $orderConverter,
        OrderFetcherInterface $orderFetcher
    ) {
        $this->orderConverter = $orderConverter;
        $this->orderFetcher   = $orderFetcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'addCartData',
            AccountEditOrderPageLoadedEvent::class => 'addCartData',
        ];
    }

    /** @param AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event */
    public function addCartData(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $cart = $event->getPage()->getCart();
        } else {
            $order = $event->getPage()->getOrder();
            $cart  = $this->convertCartFromOrder($order, $event->getContext());
        }

        $this->hidePayonePaymentMethodsOnZeroAmountCart($page, $cart, $event->getSalesChannelContext());

        if ($cart->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        /** @var null|CheckoutCartPaymentData $extension */
        $extension = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if (null !== $extension && null !== $payoneData) {
            $payoneData->assign([
                'workOrderId' => $extension->getWorkorderId(),
                'cartHash'    => $extension->getCartHash(),
            ]);
        }

        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }

    /** @param AccountEditOrderPage|CheckoutConfirmPage $page */
    private function hidePayonePaymentMethodsOnZeroAmountCart(Page $page, Cart $cart, SalesChannelContext $salesChannelContext): void
    {
        $totalAmount = (int) round($cart->getPrice()->getTotalPrice() * (10 ** $salesChannelContext->getCurrency()->getDecimalPrecision()));

        if ($totalAmount > 0) {
            return;
        }

        $page->setPaymentMethods(
            $page->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) {
                return mb_strpos($paymentMethod->getHandlerIdentifier(), 'PayonePayment') === false;
            })
        );

        $salesChannelContext->assign(['paymentMethods' => $page->getPaymentMethods()]);
    }

    private function convertCartFromOrder(OrderEntity $orderEntity, Context $context): Cart
    {
        $order = $this->orderFetcher->getOrderById($orderEntity->getId(), $context);

        if (null === $order) {
            throw new LogicException('could not find order via id');
        }

        return $this->orderConverter->convertToCart($order, $context);
    }
}
