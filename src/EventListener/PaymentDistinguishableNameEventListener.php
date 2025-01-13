<?php declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayonePaypalV2;
use PayonePayment\PaymentMethod\PayonePaypalV2Express;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentDistinguishableNameEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => 'updateDistinguishablePaymentNameForPayPalV2',
        ];
    }

    public function updateDistinguishablePaymentNameForPayPalV2(EntityLoadedEvent $event): void
    {
        /** @var PaymentMethodEntity $payment */
        foreach ($event->getEntities() as $payment) {
            // Technical name is nullable <6.7.0
            $technicalName = $payment->getTechnicalName() ?? '';

            if (\in_array($technicalName, [PayonePaypalV2::TECHNICAL_NAME, PayonePaypalV2Express::TECHNICAL_NAME], true)) {
                $distinguishableName = str_replace('PayPal', 'PayPal v2', $payment->getTranslation('distinguishableName'));
                $payment->setDistinguishableName($distinguishableName);
                $payment->addTranslated('distinguishableName', $distinguishableName);
            }
        }
    }
}
