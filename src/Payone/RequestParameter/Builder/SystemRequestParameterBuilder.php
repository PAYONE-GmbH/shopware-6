<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\RequestParameter\Struct\CheckoutDetailsStruct;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Struct\Struct;

class SystemRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var PluginService */
    private $pluginService;

    /** @var string */
    private $shopwareVersion;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        PluginService $pluginService,
        string $shopwareVersion,
        ConfigReaderInterface $configReader
    ) {
        $this->pluginService   = $pluginService;
        $this->shopwareVersion = $shopwareVersion;
        $this->configReader    = $configReader;
    }

    /**
     * @param CheckoutDetailsStruct|CreditCardCheckStruct|FinancialTransactionStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct $arguments
     */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        if ($arguments instanceof FinancialTransactionStruct) {
            $context        = $arguments->getContext();
            $salesChannelId = $arguments->getPaymentTransaction()->getOrder()->getSalesChannelId();
        } else {
            $context        = $arguments->getSalesChannelContext()->getContext();
            $salesChannelId = $arguments->getSalesChannelContext()->getSalesChannel()->getId();
        }

        $paymentMethod       = $arguments->getPaymentMethod();
        $configuration       = $this->configReader->read($salesChannelId);
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod];

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

    public function supports(Struct $arguments): bool
    {
        if ($arguments instanceof PaymentTransactionStruct) {
            return true;
        }

        if ($arguments instanceof CheckoutDetailsStruct) {
            return true;
        }

        if ($arguments instanceof CreditCardCheckStruct) {
            return true;
        }

        if ($arguments instanceof PayolutionAdditionalActionStruct) {
            return true;
        }

        if ($arguments instanceof FinancialTransactionStruct) {
            return true;
        }

        return false;
    }
}
