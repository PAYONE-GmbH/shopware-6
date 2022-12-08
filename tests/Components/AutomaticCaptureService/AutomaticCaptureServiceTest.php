<?php

declare(strict_types=1);

namespace PayonePayment\Components\AutomaticCaptureService;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionHandler\Capture\CaptureTransactionHandlerInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @covers \PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureService
 */
class AutomaticCaptureServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItCapturesAutomatically(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $config = $this->createMock(Configuration::class);
        $config
            ->expects(static::once())
            ->method('getBool')
            ->with(static::equalTo('prepaymentAutomaticCapture'))
            ->willReturn(true)
        ;

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->willReturn($config);

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler
            ->expects(static::once())
            ->method('capture')
            ->with(
                static::callback(static function (ParameterBag $parameterBag) {
                    static::assertSame(100.10, $parameterBag->get('amount'));
                    static::assertTrue($parameterBag->get('complete'));
                    static::assertTrue($parameterBag->get('includeShippingCosts'));
                    static::assertSame(
                        [
                            'id' => 'the-line-item-id',
                            'quantity' => 2,
                            'unit_price' => 50.05,
                            'selected' => false,
                        ],
                        array_values($parameterBag->get('orderLines'))[0]
                    );
                    static::assertSame('the-order-transaction-id', $parameterBag->get('orderTransactionId'));
                    static::assertSame('the-payone-order-id', $parameterBag->get('payone_order_id'));

                    return true;
                })
            )
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture successful!'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getHandlerIdentifier')
            ->willReturn(PayonePrepaymentPaymentHandler::class)
        ;

        $payoneExtension = $this->createMock(PayonePaymentOrderTransactionDataEntity::class);
        $payoneExtension->expects(static::once())->method('getAllowCapture')->willReturn(true);
        $payoneExtension->expects(static::once())->method('getCapturedAmount')->willReturn(0);
        $payoneExtension->expects(static::once())->method('getTransactionId')->willReturn('the-payone-order-id');

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($payoneExtension)
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getId')
            ->willReturn('the-order-transaction-id')
        ;

        $lineItem = $this->createMock(OrderLineItemEntity::class);
        $lineItem->expects(static::once())->method('getId')->willReturn('the-line-item-id');
        $lineItem->expects(static::once())->method('getQuantity')->willReturn(2);
        $lineItem->expects(static::once())->method('getUnitPrice')->willReturn(50.05);

        $orderLineItemCollection = new OrderLineItemCollection([$lineItem]);

        $order = $this->createMock(OrderEntity::class);
        $order->expects(static::once())->method('getLineItems')->willReturn($orderLineItemCollection);
        $order->expects(static::once())->method('getAmountTotal')->willReturn(100.10);

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);
        $paymentTransaction->expects(static::once())->method('getOrder')->willReturn($order);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseOfMissingExtension(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::never())->method('read');

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Missing Extension'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn(null)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseOfMissingPaymentMethod(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::never())->method('read');

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Missing Payment Method'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($this->createMock(PayonePaymentOrderTransactionDataEntity::class))
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn(null)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseOfMissingConfigPrefix(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::never())->method('read');

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Missing Config Prefix'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod->expects(static::once())->method('getHandlerIdentifier')->willReturn('UnknownHandler');

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($this->createMock(PayonePaymentOrderTransactionDataEntity::class))
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseItIsDisabled(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $config = $this->createMock(Configuration::class);
        $config
            ->expects(static::once())
            ->method('getBool')
            ->with(static::equalTo('prepaymentAutomaticCapture'))
            ->willReturn(false)
        ;

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->willReturn($config);

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Not enabled'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getHandlerIdentifier')
            ->willReturn(PayonePrepaymentPaymentHandler::class)
        ;

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($this->createMock(PayonePaymentOrderTransactionDataEntity::class))
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseCaptureIsNotAllowed(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $config = $this->createMock(Configuration::class);
        $config
            ->expects(static::once())
            ->method('getBool')
            ->with(static::equalTo('prepaymentAutomaticCapture'))
            ->willReturn(true)
        ;

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->willReturn($config);

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Not capturable'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getHandlerIdentifier')
            ->willReturn(PayonePrepaymentPaymentHandler::class)
        ;

        $payoneExtension = $this->createMock(PayonePaymentOrderTransactionDataEntity::class);
        $payoneExtension->expects(static::once())->method('getAllowCapture')->willReturn(false);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($payoneExtension)
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseThereIsAlreadyACapturedAmount(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $config = $this->createMock(Configuration::class);
        $config
            ->expects(static::once())
            ->method('getBool')
            ->with(static::equalTo('prepaymentAutomaticCapture'))
            ->willReturn(true)
        ;

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->willReturn($config);

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Not capturable'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getHandlerIdentifier')
            ->willReturn(PayonePrepaymentPaymentHandler::class)
        ;

        $payoneExtension = $this->createMock(PayonePaymentOrderTransactionDataEntity::class);
        $payoneExtension->expects(static::once())->method('getAllowCapture')->willReturn(true);
        $payoneExtension->expects(static::once())->method('getCapturedAmount')->willReturn(100);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($payoneExtension)
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }

    public function testItNotCapturesAutomaticallyBecauseOfMissingLineItems(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $config = $this->createMock(Configuration::class);
        $config
            ->expects(static::once())
            ->method('getBool')
            ->with(static::equalTo('prepaymentAutomaticCapture'))
            ->willReturn(true)
        ;

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->expects(static::once())->method('read')->willReturn($config);

        $captureTransactionHandler = $this->createMock(CaptureTransactionHandlerInterface::class);
        $captureTransactionHandler->expects(static::never())->method('capture');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(static::once())
            ->method('debug')
            ->with(static::equalTo('Automatic capture not possible: Missing line items'))
        ;

        $automaticCaptureService = new AutomaticCaptureService($configReader, $captureTransactionHandler, $logger);

        $paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getHandlerIdentifier')
            ->willReturn(PayonePrepaymentPaymentHandler::class)
        ;

        $payoneExtension = $this->createMock(PayonePaymentOrderTransactionDataEntity::class);
        $payoneExtension->expects(static::once())->method('getAllowCapture')->willReturn(true);
        $payoneExtension->expects(static::once())->method('getCapturedAmount')->willReturn(0);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction
            ->expects(static::once())
            ->method('getExtension')
            ->willReturn($payoneExtension)
        ;
        $orderTransaction
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod)
        ;

        $order = $this->createMock(OrderEntity::class);
        $order->expects(static::once())->method('getLineItems')->willReturn(null);

        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(static::once())->method('getOrderTransaction')->willReturn($orderTransaction);
        $paymentTransaction->expects(static::once())->method('getOrder')->willReturn($order);

        $automaticCaptureService->captureIfPossible($paymentTransaction, $salesChannelContext);
    }
}
