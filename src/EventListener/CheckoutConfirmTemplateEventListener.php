<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmTemplateEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'addPayonePageData',
            AccountEditOrderPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if (!$this->isPayonePayment($context->getPaymentMethod())) {
            return;
        }

        $template = $this->getTemplateFromPaymentMethod($context->getPaymentMethod());

        if ($page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        if (null !== $payoneData) {
            $payoneData->assign([
                'template' => $template,
            ]);
        }

        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }

    private function getTemplateFromPaymentMethod(PaymentMethodEntity $paymentMethod): ?string
    {
        $customFields = $paymentMethod->getCustomFields();

        if (!empty($customFields[CustomFieldInstaller::TEMPLATE])) {
            return $customFields[CustomFieldInstaller::TEMPLATE];
        }

        return null;
    }

    private function isPayonePayment(PaymentMethodEntity $paymentMethod): bool
    {
        $customFields = $paymentMethod->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::IS_PAYONE])) {
            return false;
        }

        if (!$customFields[CustomFieldInstaller::IS_PAYONE]) {
            return false;
        }

        return true;
    }
}
