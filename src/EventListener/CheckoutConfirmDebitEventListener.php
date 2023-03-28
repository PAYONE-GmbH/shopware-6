<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\StoreApi\Route\AbstractMandateRoute;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmDebitEventListener implements EventSubscriberInterface
{
    private AbstractMandateRoute $mandateRoute;

    public function __construct(AbstractMandateRoute $mandateRoute)
    {
        $this->mandateRoute = $mandateRoute;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addPayonePageData',
            AccountEditOrderPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() !== PayoneDebit::UUID) {
            return;
        }

        $savedMandates = null;

        if ($context->getCustomer() !== null) {
            $savedMandates = $this->mandateRoute->load($context)->getSearchResult();
        }

        $payoneData = $page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)
            ? $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME)
            : new CheckoutConfirmPaymentData();

        if ($payoneData !== null) {
            $payoneData->assign([
                'savedMandates' => $savedMandates,
            ]);

            $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
        }
    }
}
