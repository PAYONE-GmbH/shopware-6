<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PayonePayment;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Framework\Plugin\PluginService;

/**
 * @implements RequestParameterEnricherInterface<AbstractRequestDto>
 */
readonly class SystemRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private PluginService $pluginService,
        private string $shopwareVersion,
        private ConfigReaderInterface $configReader,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $context             = $arguments->getContext();
        $salesChannelId      = $arguments->getSalesChannelId();
        $paymentHandler      = $arguments->paymentHandler;
        $configuration       = $this->configReader->read($salesChannelId);
        $configurationPrefix = $paymentHandler->getConfigKeyPrefix();

        $accountId = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_ACCOUNT_ID,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_ACCOUNT_ID),
        );

        $merchantId = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_MERCHANT_ID,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID),
        );

        $portalId = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_PORTAL_ID,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_PORTAL_ID),
        );

        $portalKey = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_PORTAL_KEY,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_PORTAL_KEY),
        );

        $plugin = $this->pluginService->getPluginByName(PayonePayment::PLUGIN_NAME, $context);

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
            'solution_name'      => 'netinventors',
            'solution_version'   => $plugin->getVersion(),
        ];
    }
}
