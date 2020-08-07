<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;

class SystemRequest
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PluginService */
    private $pluginService;

    /** @var string */
    private $shopwareVersion;

    /** @var Configuration */
    private $configuration;

    /** @var string */
    private $configurationPrefix;

    public function __construct(
        ConfigReaderInterface $configReader,
        PluginService $pluginService,
        string $shopwareVersion
    ) {
        $this->configReader    = $configReader;
        $this->pluginService   = $pluginService;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function getRequestParameters(string $salesChannel, string $configurationPrefix, Context $context): array
    {
        $this->configuration       = $this->configReader->read($salesChannel);
        $this->configurationPrefix = $configurationPrefix;

        $accountId  = $this->configuration->get(sprintf('%sAccountId', $configurationPrefix), $this->configuration->get('accountId'));
        $merchantId = $this->configuration->get(sprintf('%sMerchantId', $configurationPrefix), $this->configuration->get('merchantId'));
        $portalId   = $this->configuration->get(sprintf('%sPortalId', $configurationPrefix), $this->configuration->get('portalId'));
        $portalKey  = $this->configuration->get(sprintf('%sPortalKey', $configurationPrefix), $this->configuration->get('portalKey'));

        $plugin = $this->pluginService->getPluginByName('PayonePayment', $context);

        return [
            'aid'                => $accountId,
            'mid'                => $merchantId,
            'portalid'           => $portalId,
            'key'                => $portalKey,
            'api_version'        => '3.10',
            'mode'               => $this->configuration->get('transactionMode'),
            'encoding'           => 'UTF-8',
            'integrator_name'    => 'shopware6',
            'integrator_version' => $this->shopwareVersion,
            'solution_name'      => 'kellerkinder',
            'solution_version'   => $plugin->getVersion(),
        ];
    }

    public function getReferenceNumber(PaymentTransaction $transaction, bool $generateNew = false): ?string
    {
        $latestReferenceNumber = $this->getLatestReferenceNumber($transaction);

        if ($latestReferenceNumber && $generateNew === false) {
            return $latestReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($transaction->getOrder());

        return $orderNumber . $suffix;
    }

    private function getLatestReferenceNumber(PaymentTransaction $transaction): ?string
    {
        /** @var null|OrderTransactionCollection $transactions */
        $transactions = $transaction->getOrder()->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $transactions = $transactions->filter(static function (OrderTransactionEntity $transaction) {
            $customFields = $transaction->getPaymentMethod()->getCustomFields();

            if (empty($customFields)) {
                return false;
            }

            if (empty($customFields[CustomFieldInstaller::IS_PAYONE])) {
                return false;
            }

            if (!$customFields[CustomFieldInstaller::IS_PAYONE]) {
                return false;
            }

            return true;
        });

        if ($transactions->count() === 0) {
            return null;
        }

        $transactions->sort(static function (OrderTransactionEntity $a, OrderTransactionEntity $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        $orderTransaction = $transactions->last();

        $customFields = $orderTransaction->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::TRANSACTION_DATA])) {
            return null;
        }

        $payoneTransactionData = array_pop($customFields[CustomFieldInstaller::TRANSACTION_DATA]);

        if (!isset($payoneTransactionData['request'])) {
            return null;
        }

        $request = $payoneTransactionData['request'];

        return (string) $request['reference'];
    }

    private function getReferenceSuffix(OrderEntity $order): string
    {
        if ($order->getTransactions()->count() <= 1) {
            return '';
        }

        return sprintf('_%d', $order->getTransactions()->count());
    }
}
