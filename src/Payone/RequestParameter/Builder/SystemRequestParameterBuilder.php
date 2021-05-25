<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SystemRequestParameterBuilder extends AbstractRequestParameterBuilder {
    private ConfigReaderInterface $configReader;

    private PluginService $pluginService;

    private string $shopwareVersion;

    private Configuration $configuration;

    private string $configurationPrefix;

    public function __construct(
        ConfigReaderInterface $configReader,
        PluginService $pluginService,
        string $shopwareVersion
    ) {
        $this->configReader    = $configReader;
        $this->pluginService   = $pluginService;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ) : array {
        $this->configuration       = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $this->configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod];

        $accountId  = $this->configuration->get(sprintf('%sAccountId', $this->configurationPrefix), $this->configuration->get('accountId'));
        $merchantId = $this->configuration->get(sprintf('%sMerchantId', $this->configurationPrefix), $this->configuration->get('merchantId'));
        $portalId   = $this->configuration->get(sprintf('%sPortalId', $this->configurationPrefix), $this->configuration->get('portalId'));
        $portalKey  = $this->configuration->get(sprintf('%sPortalKey', $this->configurationPrefix), $this->configuration->get('portalKey'));

        $plugin = $this->pluginService->getPluginByName('PayonePayment', $salesChannelContext->getContext());

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

    public function supports(string $paymentMethod, string $action = '') : bool {
        if($paymentMethod === PayonePaypal::class && $action === 'authorize') {
            return true;
        }

        if($paymentMethod === PayonePaypal::class && $action === 'preauthorize') {
            return true;
        }

        return false;
    }
}
