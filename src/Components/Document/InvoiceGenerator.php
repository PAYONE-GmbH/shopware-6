<?php

declare(strict_types=1);

namespace PayonePayment\Components\Document;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Document\Struct\InvoiceDocumentData;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceGenerator implements DocumentGeneratorInterface
{
    /** @var DocumentGeneratorInterface */
    private $decoratedService;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(DocumentGeneratorInterface $decoratedService, ConfigReaderInterface $configReader)
    {
        $this->decoratedService = $decoratedService;
        $this->configReader     = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(): string
    {
        return $this->decoratedService->supports();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        if ($this->isPayoneInstallmentPaymentMethod($order)) {
            $this->addInvoiceDocumentExtension($order);
        }

        return $this->decoratedService->generate($order, $config, $context, $templatePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName(DocumentConfiguration $config): string
    {
        return $this->decoratedService->getFileName($config);
    }

    private function isPayoneInstallmentPaymentMethod(OrderEntity $order): bool
    {
        if (null !== $order->getTransactions()) {
            foreach ($order->getTransactions() as $transaction) {
                if ($transaction->getPaymentMethodId() === PayonePayolutionInvoicing::UUID) {
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
