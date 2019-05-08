<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Struct\PayonePaymentData;
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    /** @var CreditCardCheckRequestFactory */
    private $requestFactory;

    public function __construct(CreditCardCheckRequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutEvents::CHECKOUT_CONFIRM_PAGE_LOADED_EVENT => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(CheckoutConfirmPageLoadedEvent $event)
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $salesChannel = $salesChannelContext->getSalesChannel();
        $context = $salesChannelContext->getContext();

        $payoneData = new PayonePaymentData();
        $payoneData->assign([
            'cardRequest' => $this->requestFactory->getRequestParameters($salesChannel, $context),
        ]);

        $event->getPage()->addExtension('payone', $payoneData);
    }
}
