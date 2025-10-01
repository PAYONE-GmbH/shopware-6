<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionHandler\Capture\CaptureTransactionHandlerInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

readonly class AutomaticCaptureService
{
    public function __construct(
        protected ConfigReaderInterface $configReader,
        protected CaptureTransactionHandlerInterface $captureTransactionHandler,
        protected LoggerInterface $logger,
        protected PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    public function captureIfPossible(
        PaymentTransaction $paymentTransaction,
        SalesChannelContext $salesChannelContext,
    ): void {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $payoneExtension  = $orderTransaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if (!$payoneExtension instanceof PayonePaymentOrderTransactionDataEntity) {
            $this->logger->debug('Automatic capture not possible: Missing Extension');

            return;
        }

        $paymentMethodEntity = $orderTransaction->getPaymentMethod();

        if (!$paymentMethodEntity) {
            $this->logger->debug('Automatic capture not possible: Missing Payment Method Entity');

            return;
        }

        $paymentMethod = $this->paymentMethodRegistry->getByHandler($paymentMethodEntity->getHandlerIdentifier());

        if (null === $paymentMethod) {
            $this->logger->debug('Automatic capture not possible: Missing Payment Method');

            return;
        }

        $configPrefix = $paymentMethod::getConfigurationPrefix();

        if (!$configPrefix) {
            $this->logger->debug('Automatic capture not possible: Missing Config Prefix');

            return;
        }

        $config                    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $automaticCaptureConfigKey = $configPrefix . 'AutomaticCapture';
        $automaticCaptureActive    = $config->getBool($automaticCaptureConfigKey);

        if (!$automaticCaptureActive) {
            $this->logger->debug('Automatic capture not possible: Not enabled');

            return;
        }

        if (!$this->isCapturable($payoneExtension)) {
            $this->logger->debug('Automatic capture not possible: Not capturable');

            return;
        }

        $order      = $paymentTransaction->getOrder();
        $orderLines = $order->getLineItems();

        if (!$orderLines) {
            $this->logger->debug('Automatic capture not possible: Missing line items');

            return;
        }

        $parameterBag = new ParameterBag([
            'amount'               => $order->getAmountTotal(),
            'complete'             => true,
            'includeShippingCosts' => true,
            'orderLines'           => $orderLines->map(static fn (OrderLineItemEntity $lineItem) => [
                'id'         => $lineItem->getId(),
                'quantity'   => $lineItem->getQuantity(),
                'unit_price' => $lineItem->getUnitPrice(),
                'selected'   => false,
            ]),
            'orderTransactionId'   => $orderTransaction->getId(),
            'payone_order_id'      => $payoneExtension->getTransactionId(),
            'salesChannel'         => $salesChannelContext->getSalesChannel()->getVars(),
        ]);

        $this->captureTransactionHandler->capture($parameterBag, $salesChannelContext->getContext());
        $this->logger->debug('Automatic capture successful!');
    }

    protected function isCapturable(PayonePaymentOrderTransactionDataEntity $payoneExtension): bool
    {
        return $payoneExtension->getAllowCapture() && 0 === (int) $payoneExtension->getCapturedAmount();
    }
}
