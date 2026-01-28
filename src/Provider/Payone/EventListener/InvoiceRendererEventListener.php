<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Provider\Payone\Dto\SecuredInvoice\InvoiceDocumentDataDto;
use PayonePayment\Provider\Payone\Extension\SecuredInvoice\SecuredInvoiceDocumentDataExtension;
use PayonePayment\Provider\Payone\PaymentMethod\SecuredInvoicePaymentMethod;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
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
            if ($this->hasSecuredInvoiceTransaction($order)) {
                $this->addInvoiceDocumentExtension($order);
            }
        }
    }

    private function hasSecuredInvoiceTransaction(OrderEntity $order): bool
    {
        if ($order->getTransactions()) {
            foreach ($order->getTransactions() as $transaction) {
                if (SecuredInvoicePaymentMethod::UUID === $transaction->getPaymentMethodId()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addInvoiceDocumentExtension(OrderEntity $order): void
    {
        $data = new SecuredInvoiceDocumentDataExtension();

        foreach ($order->getTransactions() as $transaction) {
            $extensions = $transaction->getExtensions();
            $extension  = $extensions[PayonePaymentOrderTransactionExtension::NAME] ?? null;

            if ($extension instanceof PayonePaymentOrderTransactionDataEntity) {
                $txData = $extension->getTransactionData() ?? [];
                foreach ($txData as $txDatum) {
                    $clearing = $txDatum['response']['clearing'] ?? [];
                    if ([] === $clearing) {
                        continue;
                    }

                    $dto = new InvoiceDocumentDataDto(
                        $clearing['BankAccount']['BankAccountHolder'] ?? null,
                        $clearing['BankAccount']['Iban'] ?? null,
                        $clearing['BankAccount']['Bic'] ?? null,
                        $clearing['DueDate'] ?? null,
                        $clearing['Reference'] ?? null,
                    );
                    $data->addDocumentData($dto);
                }
            }
        }

        $order->addExtension(SecuredInvoiceDocumentDataExtension::EXTENSION_NAME, $data);
    }
}
