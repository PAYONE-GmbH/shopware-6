<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayonePaysafeInstallment;
use PayonePayment\PaymentMethod\PayonePaysafeInvoicing;
use PayonePayment\Payone\Client\PayoneClient;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmCartDataEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addCartData',
        ];
    }

    public function addCartData(CheckoutConfirmPageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if ($page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $event->getPage()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        /** @var null|CheckoutCartPaymentData $extension */
        $extension = $event->getPage()->getCart()->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if (null !== $extension) {
            $payoneData->assign([
                'workOrderId' => $extension->getWorkorderId(),
                'cartHash'    => $extension->getCartHash(),
            ]);
        }

        $event->getPage()->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);

        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }
}
