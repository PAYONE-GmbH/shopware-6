<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PackageVersions\Versions;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;

class SystemRequest
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PluginService */
    private $pluginService;

    public function __construct(
        ConfigReaderInterface $configReader,
        PluginService $pluginService
    ) {
        $this->configReader  = $configReader;
        $this->pluginService = $pluginService;
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
            'integrator_name'    => 'kellerkinder',
            'integrator_version' => $plugin->getVersion(),
            'solution_name'      => 'shopware6',
            'solution_version'   => Versions::getVersion('shopware/platform'),
        ];
    }
}
