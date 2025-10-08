<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class HideNoLongerSupportedPaymentMetdhods implements EventSubscriberInterface
{
    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_SEARCH_RESULT_LOADED_EVENT => 'onPaymentMethodSearchResultLoaded',
        ];
    }

    public function onPaymentMethodSearchResultLoaded(EntitySearchResultLoadedEvent $event): void
    {
        if (!$event->getContext()->getSource() instanceof AdminApiSource) {
            return;
        }

        $result   = $event->getResult();
        $entities = $result->getElements();

        foreach ($entities as $id => $entity) {
            if (!$this->paymentMethodRegistry->hasId($id)) {
                continue;
            }

            $payonePaymentMethod = $this->paymentMethodRegistry->getById($id);

            if ($payonePaymentMethod instanceof NoLongerSupportedPaymentMethodInterface) {
                $result->remove($id);
            }
        }
    }
}
