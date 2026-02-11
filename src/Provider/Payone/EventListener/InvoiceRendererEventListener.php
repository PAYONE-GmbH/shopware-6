<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Provider\Payone\Extension\SecuredInvoice\SecuredInvoiceDocumentDataExtension;
use PayonePayment\Provider\Payone\PaymentMethod\SecuredInvoicePaymentMethod;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InvoiceRendererEventListener implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceOrdersEvent::class => 'onInvoiceOrdersLoaded',
        ];
    }

    public function onInvoiceOrdersLoaded(InvoiceOrdersEvent $event): void
    {
        $orders = $event->getOrders();

        foreach ($orders as $order) {
            $transaction = $order->getTransactions()?->last();
            if (
                $transaction instanceof OrderTransactionEntity
                && SecuredInvoicePaymentMethod::UUID === $transaction->getPaymentMethodId()
            ) {
                $this->addInvoiceDocumentExtension($order, $transaction);
            }
        }
    }

    private function addInvoiceDocumentExtension(OrderEntity $order, OrderTransactionEntity $transaction): void
    {
        $extensions = $transaction->getExtensions();
        $extension  = $extensions[PayonePaymentOrderTransactionExtension::NAME] ?? null;
        if (!$extension instanceof PayonePaymentOrderTransactionDataEntity) {
            return;
        }

        $txData = $extension->getTransactionData() ?? [];
        if ([] === $txData) {
            return;
        }

        foreach (\array_reverse($txData) as $txDataItem) {
            $clearing = $txDataItem['response']['clearing'] ?? [];
            if (!isset($clearing['BankAccount'])) {
                continue;
            }

            $ext = new SecuredInvoiceDocumentDataExtension(
                $clearing['BankAccount']['BankAccountHolder'] ?? null,
                $clearing['BankAccount']['Iban'] ?? null,
                $clearing['BankAccount']['Bic'] ?? null,
                $clearing['DueDate'] ?? null,
                $clearing['Reference'] ?? null,
            );
            $order->addExtension(SecuredInvoiceDocumentDataExtension::EXTENSION_NAME, $ext);

            break;
        }
    }
}
