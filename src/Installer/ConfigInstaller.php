<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigInstaller implements InstallerInterface
{
    public const DEFAULT_VALUES = [
        'transactionMode' => 'test',

        // Default authorization modes for payment methods
        'creditCardAuthorizationMethod'            => 'preauthorization',
        'debitAuthorizationMethod'                 => 'authorization',
        'payolutionDebitAuthorizationMethod'       => 'preauthorization',
        'payolutionInstallmentAuthorizationMethod' => 'authorization',
        'payolutionInvoicingAuthorizationMethod'   => 'preauthorization',
        'paypalAuthorizationMethod'                => 'preauthorization',
        'paypalExpressAuthorizationMethod'         => 'preauthorization',
        'sofortAuthorizationMethod'                => 'authorization',
    ];

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(ContainerInterface $container)
    {
        $this->systemConfigService = $container->get(SystemConfigService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        if (empty(self::DEFAULT_VALUES)) {
            return;
        }

        $this->setDefaultValues();
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        if (empty(self::DEFAULT_VALUES)) {
            return;
        }

        $this->setDefaultValues();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function setDefaultValues()
    {
        $domain = 'PayonePayment.settings.';

        foreach (self::DEFAULT_VALUES as $key => $value) {
            $configKey = $domain . $key;

            $currentValue = $this->systemConfigService->get($configKey);

            if ($currentValue !== null) {
                continue;
            }

            $this->systemConfigService->set($configKey, $value);
        }
    }
}
