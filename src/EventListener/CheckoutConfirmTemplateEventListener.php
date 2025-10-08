<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmTemplateEventListener implements EventSubscriberInterface
{
    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'addPayonePageData',
            AccountEditOrderPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): void
    {
        $payonePaymentMethod = $this->getPayonePaymentMethod($event->getSalesChannelContext()->getPaymentMethod());

        if (null === $payonePaymentMethod) {
            return;
        }

        $page       = $event->getPage();
        $template   = $payonePaymentMethod->getTemplate();
        $payoneData = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME) ?? new CheckoutConfirmPaymentData();

        $payoneData->assign([ 'template' => $template ]);
        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }

    /**
     * @param PaymentMethodEntity $paymentMethod
     *
     * @return PaymentMethodInterface|null
     */
    private function getPayonePaymentMethod(PaymentMethodEntity $paymentMethod): PaymentMethodInterface|null
    {
        /** @var PaymentMethodInterface $payonePaymentMethod */
        foreach ($this->paymentMethodRegistry as $payonePaymentMethod) {
            if ($payonePaymentMethod::getId() === $paymentMethod->getId()) {
                return $payonePaymentMethod;
            }
        }

        return null;
    }
}
