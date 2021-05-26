<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SystemRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    private PluginService $pluginService;

    private string $shopwareVersion;

    public function __construct(
        PluginService $pluginService,
        string $shopwareVersion
    ) {
        $this->pluginService   = $pluginService;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ): array {
        $configuration       = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES_BY_METHOD[$paymentMethod];

        $accountId  = $configuration->get(sprintf('%sAccountId', $configurationPrefix), $configuration->get('accountId'));
        $merchantId = $configuration->get(sprintf('%sMerchantId', $configurationPrefix), $configuration->get('merchantId'));
        $portalId   = $configuration->get(sprintf('%sPortalId', $configurationPrefix), $configuration->get('portalId'));
        $portalKey  = $configuration->get(sprintf('%sPortalKey', $configurationPrefix), $configuration->get('portalKey'));

        $plugin = $this->pluginService->getPluginByName('PayonePayment', $salesChannelContext->getContext());

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

    public function supports(string $paymentMethod, string $action = ''): bool
    {
        //TODO: may switch case, because system request is almost needed everywhere
        if ($paymentMethod === PayonePaypal::class && $action === self::REQUEST_ACTION_AUTHORIZE) {
            return true;
        }

        if ($paymentMethod === PayonePaypal::class && $action === self::REQUEST_ACTION_PREAUTHORIZE) {
            return true;
        }

        return false;
    }
}
