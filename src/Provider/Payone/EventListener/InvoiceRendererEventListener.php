<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Document\Struct\InvoiceDocumentData;
use PayonePayment\Provider\Payolution\PaymentMethod\InvoicePaymentMethod;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class InvoiceRendererEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ConfigReaderInterface $configReader,
    ) {
    }

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
            if ($this->hasPayolutionInvoicingTransaction($order)) {
                $this->addInvoiceDocumentExtension($order);
            }
        }
    }

    private function hasPayolutionInvoicingTransaction(OrderEntity $order): bool
    {
        if ($order->getTransactions()) {
            foreach ($order->getTransactions() as $transaction) {
                if (InvoicePaymentMethod::UUID === $transaction->getPaymentMethodId()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addInvoiceDocumentExtension(OrderEntity $order): void
    {
        $configuration = $this->configReader->read($order->getSalesChannelId());

        $iban = $configuration->get('payolutionInvoicingIban');
        $bic  = $configuration->get('payolutionInvoicingBic');

        if (empty($iban) || empty($bic)) {
            return;
        }

        $extension = new InvoiceDocumentData();
        $extension->assign([
            'iban' => $iban,
            'bic'  => $bic,
        ]);

        $order->addExtension(InvoiceDocumentData::EXTENSION_NAME, $extension);
    }
}
