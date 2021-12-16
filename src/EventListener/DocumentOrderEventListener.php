<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use Shopware\Core\Checkout\Document\Event\DocumentOrderCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentOrderEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentOrderCriteriaEvent::class => 'addPayonePaymentTransactionDataExtensionAssociation',
        ];
    }

    public function addPayonePaymentTransactionDataExtensionAssociation(DocumentOrderCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('transactions.' . PayonePaymentOrderTransactionExtension::NAME);
    }
}
