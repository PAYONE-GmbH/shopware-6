<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
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
        $configuration = $this->configReader->read($salesChannel);

        $accountId  = $configuration->get(sprintf('%sAccountId', $configurationPrefix), $configuration->get('accountId'));
        $merchantId = $configuration->get(sprintf('%sMerchantId', $configurationPrefix), $configuration->get('merchantId'));
        $portalId   = $configuration->get(sprintf('%sPortalId', $configurationPrefix), $configuration->get('portalId'));
        $portalKey  = $configuration->get(sprintf('%sPortalKey', $configurationPrefix), $configuration->get('portalKey'));

        $plugin = $this->pluginService->getPluginByName('PayonePayment', $context);

        return [
            'aid'                => $accountId,
            'mid'                => $merchantId,
            'portalid'           => $portalId,
            'key'                => $portalKey,
            'api_version'        => '3.10',
            'mode'               => $configuration->get('transactionMode'),
            'encoding'           => 'UTF-8',
            'integrator_name'    => 'shopware6',
            'integrator_version' => $this->shopwareVersion,
            'solution_name'      => 'kellerkinder',
            'solution_version'   => $plugin->getVersion(),
        ];
    }
}
