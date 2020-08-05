<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
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

    /**
     * Delivers last saved reference or generate new one
     */
    public function getReferenceNumber(PaymentTransaction $transaction, bool $generateNew = false): string
    {
        $latestReferenceNumber = $this->getLatestReferenceNumber($transaction);

        if ($latestReferenceNumber && $generateNew === false) {
            return $latestReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($latestReferenceNumber);

        return $orderNumber . $suffix;
    }

    /**
     * Fetch last used reference number if any exist or null if not
     */
    private function getLatestReferenceNumber(PaymentTransaction $transaction): ?string
    {
        $transactions = $transaction->getOrder()->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $transactions = $transactions->filter(static function ($transaction) {
            $customFields = $transaction->getCustomFields();

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

        $elements = $transactions->getElements();

        $orderTransaction = array_pop($elements);
        $customFields     = $orderTransaction->getCustomFields();

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

    /**
     * Create a new suffix by analyzing lastReferenceNumber
     */
    private function getReferenceSuffix(?string $latestReferenceNumber): string
    {
        if ($latestReferenceNumber === null) {
            return '_0';
        }

        $referenceParts = explode('_', $latestReferenceNumber);
        $suffixNumber   = (int) array_pop($referenceParts);
        ++$suffixNumber;
        $suffixNumber = (string) $suffixNumber;

        return '_' . $suffixNumber;
    }
}
