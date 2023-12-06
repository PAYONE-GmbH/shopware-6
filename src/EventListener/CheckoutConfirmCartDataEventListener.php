<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Installer\PaymentMethodInstaller;
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Exception\LogicException;

class CheckoutConfirmCartDataEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderConverter $orderConverter,
        private readonly OrderFetcherInterface $orderFetcher,
        private readonly CurrencyPrecisionInterface $currencyPrecision
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addCartData',
            AccountEditOrderPageLoadedEvent::class => 'addCartData',
        ];
    }

    public function addCartData(AccountEditOrderPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $cart = $event->getPage()->getCart();
        } else {
            $order = $event->getPage()->getOrder();
            $cart = $this->convertCartFromOrder($order, $event->getContext());
        }

        $this->hidePayonePaymentMethodsOnZeroAmountCart($page, $cart, $event->getSalesChannelContext());

        if ($cart->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        /** @var CheckoutCartPaymentData|null $extension */
        $extension = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if ($extension !== null && $payoneData !== null) {
            $payoneData->assign([
                'workOrderId' => $extension->getWorkorderId(),
                'cartHash' => $extension->getCartHash(),
            ]);

            $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
        }
    }

    private function hidePayonePaymentMethodsOnZeroAmountCart(
        AccountEditOrderPage|CheckoutConfirmPage $page,
        Cart $cart,
        SalesChannelContext $salesChannelContext
    ): void {
        $totalAmount = $this->currencyPrecision->getRoundedItemAmount($cart->getPrice()->getTotalPrice(), $salesChannelContext->getCurrency());

        if ($totalAmount > 0) {
            return;
        }

        $page->setPaymentMethods(
            $page->getPaymentMethods()->filter(
                static fn (PaymentMethodEntity $paymentMethod) => !str_contains($paymentMethod->getHandlerIdentifier(), PaymentMethodInstaller::HANDLER_IDENTIFIER_ROOT_NAMESPACE)
            )
        );

        $salesChannelContext->assign(['paymentMethods' => $page->getPaymentMethods()]);
    }

    private function convertCartFromOrder(OrderEntity $orderEntity, Context $context): Cart
    {
        $order = $this->orderFetcher->getOrderById($orderEntity->getId(), $context);

        if ($order === null) {
            throw new LogicException('could not find order via id');
        }

        return $this->orderConverter->convertToCart($order, $context);
    }
}
