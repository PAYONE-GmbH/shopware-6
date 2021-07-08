<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\CheckoutDetailsStruct;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\GetFileStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use PayonePayment\PayonePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;

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
     * @param CheckoutDetailsStruct|CreditCardCheckStruct|FinancialTransactionStruct|GetFileStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $context        = $this->getContext($arguments);
        $salesChannelId = $this->getSalesChannelId($arguments);

        $paymentMethod       = $arguments->getPaymentMethod();
        $configuration       = $this->configReader->read($salesChannelId);
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod];

        $accountId  = $configuration->getByPrefix(ConfigInstaller::CONFIG_FIELD_ACCOUNT_ID, $configurationPrefix, $configuration->get(ConfigInstaller::CONFIG_FIELD_ACCOUNT_ID));
        $merchantId = $configuration->getByPrefix(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID, $configurationPrefix, $configuration->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID));
        $portalId   = $configuration->getByPrefix(ConfigInstaller::CONFIG_FIELD_PORTAL_ID, $configurationPrefix, $configuration->get(ConfigInstaller::CONFIG_FIELD_PORTAL_ID));
        $portalKey  = $configuration->getByPrefix(ConfigInstaller::CONFIG_FIELD_PORTAL_KEY, $configurationPrefix, $configuration->get(ConfigInstaller::CONFIG_FIELD_PORTAL_KEY));

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
            'solution_name'      => 'kellerkinder',
            'solution_version'   => $plugin->getVersion(),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if ($arguments instanceof TestCredentialsStruct) {
            return false;
        }

        return true;
    }

    /**
     * @param CheckoutDetailsStruct|CreditCardCheckStruct|FinancialTransactionStruct|GetFileStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct $arguments
     */
    private function getContext(AbstractRequestParameterStruct $arguments): Context
    {
        if ($arguments instanceof FinancialTransactionStruct) {
            return $arguments->getContext();
        }

        return $arguments->getSalesChannelContext()->getContext();
    }

    /**
     * @param CheckoutDetailsStruct|CreditCardCheckStruct|FinancialTransactionStruct|GetFileStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct $arguments
     */
    private function getSalesChannelId(AbstractRequestParameterStruct $arguments): string
    {
        if ($arguments instanceof FinancialTransactionStruct) {
            return $arguments->getPaymentTransaction()->getOrder()->getSalesChannelId();
        }

        return $arguments->getSalesChannelContext()->getSalesChannel()->getId();
    }
}
