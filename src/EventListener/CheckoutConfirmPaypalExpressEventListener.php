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

class CheckoutConfirmPaypalExpressEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hideInternalPaymentMethods',
        ];
    }

    public function hideInternalPaymentMethods(CheckoutConfirmPageLoadedEvent $event)
    {
        $internalPaymentMethods = [
            PayonePaypalExpress::UUID,
        ];

        $context = $event->getSalesChannelContext();

        $event->getPage()->setPaymentMethods(
            $event->getPage()->getPaymentMethods()->filter(
                static function (PaymentMethodEntity $entity) use ($internalPaymentMethods, $context) {
                    if ($context->getPaymentMethod()->getId() === $entity->getId()) {
                        return true;
                    }

                    return !in_array($entity->getId(), $internalPaymentMethods, true);
                }
            )
        );
    }
}
