<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigInstaller implements InstallerInterface
{
    final public const CONFIG_FIELD_ACCOUNT_ID = 'accountId';

    final public const CONFIG_FIELD_MERCHANT_ID = 'merchantId';

    final public const CONFIG_FIELD_PORTAL_ID = 'portalId';

    final public const CONFIG_FIELD_PORTAL_KEY = 'portalKey';

    final public const CONFIG_FIELD_TRANSACTION_MODE = 'transactionMode';

    final public const CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA = 'unzerInvoicingTransferCompanyData';

    final public const CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_NAME = 'unzerInstallmentChannelName';

    final public const CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_PASSWORD = 'unzerInstallmentChannelPassword';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    #[\Override]
    public function install(InstallContext $context): void
    {
        $this->setDefaultValues();
    }

    #[\Override]
    public function update(UpdateContext $context): void
    {
        $this->setDefaultValues();
    }

    #[\Override]
    public function uninstall(UninstallContext $context): void
    {
        // Nothing to do here
    }

    #[\Override]
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    #[\Override]
    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function setDefaultValues(): void
    {
        $domain = 'PayonePayment.settings.';

        foreach (SettingsDefaults::DEFAULT_VALUES as $key => $value) {
            $configKey = $domain . $key;

            $currentValue = $this->systemConfigService->get($configKey);

            if (null !== $currentValue) {
                continue;
            }

            $this->systemConfigService->set($configKey, $value);
        }

        foreach (SettingsDefaults::UPDATE_VALUES as $key => $values) {
            foreach ($values as $from => $to) {
                $configKey = $domain . $key;

                $currentValue = $this->systemConfigService->get($configKey);

                if (null !== $currentValue && $currentValue !== $from) {
                    continue;
                }

                $this->systemConfigService->set($configKey, $to);
            }
        }
    }
}
