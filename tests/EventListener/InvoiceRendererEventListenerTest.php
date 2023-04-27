<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Document\Struct\InvoiceDocumentData;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Struct\Configuration;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;

/**
 * @covers \PayonePayment\EventListener\InvoiceRendererEventListener
 */
class InvoiceRendererEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsInvoiceDocumentDataExtensionToOrder(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setPaymentMethodId(PayonePayolutionInvoicing::UUID);
        $transaction->setUniqueIdentifier(Uuid::randomHex());

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([$transaction]));
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setUniqueIdentifier(Uuid::randomHex());

        $event = new InvoiceOrdersEvent(new OrderCollection([$order]), Context::createDefaultContext());

        $configuration = new Configuration([
            'payolutionInvoicingIban' => 'the-iban',
            'payolutionInvoicingBic' => 'the-bic',
        ]);

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->with(TestDefaults::SALES_CHANNEL)->willReturn($configuration);

        $listener = new InvoiceRendererEventListener($configReader);
        $listener->onInvoiceOrdersLoaded($event);

        static::assertTrue($order->hasExtension(InvoiceDocumentData::EXTENSION_NAME));

        /** @var InvoiceDocumentData $extension */
        $extension = $order->getExtension(InvoiceDocumentData::EXTENSION_NAME);

        static::assertSame('the-iban', $extension->getIban());
        static::assertSame('the-bic', $extension->getBic());
    }

    public function testItNotAddsInvoiceDocumentDataExtensionToOrderOnWrongPaymentMethod(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setPaymentMethodId(PayonePayolutionInstallment::UUID);
        $transaction->setUniqueIdentifier(Uuid::randomHex());

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([$transaction]));
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setUniqueIdentifier(Uuid::randomHex());

        $event = new InvoiceOrdersEvent(new OrderCollection([$order]), Context::createDefaultContext());

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::never())->method('read');

        $listener = new InvoiceRendererEventListener($configReader);
        $listener->onInvoiceOrdersLoaded($event);

        static::assertFalse($order->hasExtension(InvoiceDocumentData::EXTENSION_NAME));
    }

    public function testItNotAddsInvoiceDocumentDataExtensionToOrderOnMissingConfiguration(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setPaymentMethodId(PayonePayolutionInvoicing::UUID);
        $transaction->setUniqueIdentifier(Uuid::randomHex());

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([$transaction]));
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setUniqueIdentifier(Uuid::randomHex());

        $event = new InvoiceOrdersEvent(new OrderCollection([$order]), Context::createDefaultContext());

        $configuration = new Configuration([]);

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->with(TestDefaults::SALES_CHANNEL)->willReturn($configuration);

        $listener = new InvoiceRendererEventListener($configReader);
        $listener->onInvoiceOrdersLoaded($event);

        static::assertFalse($order->hasExtension(InvoiceDocumentData::EXTENSION_NAME));
    }
}
