<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
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
     *
     * @param bool $generateNew
     */
    public function getReferenceNumber(PaymentTransaction $transaction, $generateNew = false): string
    {
        $lastReferenceNumber = $this->getLastReferenceNumber($transaction);

        if ($lastReferenceNumber && $generateNew === false) {
            return $lastReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($lastReferenceNumber);

        return $orderNumber . $suffix;
    }

    /**
     * Fetch last used reference number if any exist or null if not
     *
     * @return string
     */
    private function getLastReferenceNumber(PaymentTransaction $transaction): ?string
    {
        $transactions = $transaction->getOrder()->getTransactions();
        $transactions = $transactions->filter(static function ($transaction) {
            if ($transaction->getCustomFields() === null) {
                return false;
            }

            return true;
        });

        $elements = $transactions->getElements();

        if (count($elements) == 0) {
            return null;
        }

        $orderTransaction      = array_pop($elements);
        $customFields          = $orderTransaction->getCustomFields();
        $payoneTransactionData = array_pop($customFields['payone_transaction_data']);
        $request               = $payoneTransactionData['request'];

        return (string) $request['reference'];
    }

    /**
     * Create a new suffix by analyzing lastReferenceNumber
     */
    private function getReferenceSuffix(?string $lastReferenceNumber): string
    {
        if ($lastReferenceNumber === null) {
            return '_0';
        }

        $referenceParts = explode('_', $lastReferenceNumber);
        $suffixNumber   = (int) array_pop($referenceParts);
        ++$suffixNumber;
        $suffixNumber = (string) $suffixNumber;

        return '_' . $suffixNumber;
    }
}
