<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use PayonePayment\PayonePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;

/**
 * @deprecated: Will be removed when Capture, Mandate and Refund RequestParameterBuilder are migrated
 *
 * TODO: Remove when Capture, Mandate and Refund RequestParameterBuilder are migrated
 */
class SystemRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly PluginService $pluginService,
        private readonly string $shopwareVersion,
        private readonly ConfigReaderInterface $configReader,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
        parent::__construct($serviceAccessor);
    }

    #[\Override]
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $context        = $this->getContext($arguments);
        $salesChannelId = $this->getSalesChannelId($arguments);

        $configuration = $this->configReader->read($salesChannelId);
        /** @var class-string<PaymentHandlerInterface> $paymentHandlerClassname */
        $paymentHandlerClassname = $arguments->getPaymentMethod();
        $paymentMethod           = $this->paymentMethodRegistry->getByHandler($paymentHandlerClassname);
        $configurationPrefix     = $paymentMethod::getConfigurationPrefix();

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
            'solution_name'      => 'netinventors',
            'solution_version'   => $plugin->getVersion(),
        ];
    }

    #[\Override]
    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return !($arguments instanceof TestCredentialsStruct);
    }

    private function getContext(AbstractRequestParameterStruct $arguments): Context
    {
        if ($arguments instanceof FinancialTransactionStruct) {
            return $arguments->getContext();
        }

        if (\method_exists($arguments, 'getSalesChannelContext')) {
            return $arguments->getSalesChannelContext()->getContext();
        }

        return Context::createCLIContext();
    }

    private function getSalesChannelId(AbstractRequestParameterStruct $arguments): string
    {
        if ($arguments instanceof FinancialTransactionStruct) {
            return $arguments->getPaymentTransaction()->getOrder()->getSalesChannelId();
        }

        if (\method_exists($arguments, 'getSalesChannelContext')) {
            return $arguments->getSalesChannelContext()->getSalesChannel()->getId();
        }

        if (\method_exists($arguments, 'getSalesChannelId')) {
            return $arguments->getSalesChannelId();
        }

        throw new \RuntimeException('missing sales channel id');
    }
}
