<?php

declare(strict_types=1);

namespace PayonePayment;

use Doctrine\DBAL\Connection;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Installer\RuleInstaller\RuleInstallerSecureInvoice;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class PayonePayment extends Plugin
{
    public const PLUGIN_NAME = 'PayonePayment';

    public function build(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');

        parent::build($container);
    }

    public function install(InstallContext $context): void
    {
        $this->getConfigInstaller()->install($context);
        $this->getCustomFieldInstaller()->install($context);
        $this->getPaymentMethodInstaller()->install($context);
        $this->getRuleInstallerSecureInvoice()->install($context);
    }

    public function update(UpdateContext $context): void
    {
        $this->getConfigInstaller()->update($context);
        $this->getCustomFieldInstaller()->update($context);
        $this->getPaymentMethodInstaller()->update($context);
        $this->getRuleInstallerSecureInvoice()->update($context);
    }

    public function activate(ActivateContext $context): void
    {
        $this->getConfigInstaller()->activate($context);
        $this->getCustomFieldInstaller()->activate($context);
        $this->getPaymentMethodInstaller()->activate($context);
        $this->getRuleInstallerSecureInvoice()->activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->getConfigInstaller()->deactivate($context);
        $this->getCustomFieldInstaller()->deactivate($context);
        $this->getPaymentMethodInstaller()->deactivate($context);
        $this->getRuleInstallerSecureInvoice()->deactivate($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->getConfigInstaller()->uninstall($context);
        $this->getCustomFieldInstaller()->uninstall($context);
        $this->getPaymentMethodInstaller()->uninstall($context);
        $this->getRuleInstallerSecureInvoice()->uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement('DROP TABLE payone_payment_card');
            $connection->executeStatement('DROP TABLE payone_payment_redirect');
            $connection->executeStatement('DROP TABLE payone_payment_mandate');

            return;
        }

        if (method_exists($connection, 'exec')) {
            /** @noinspection PhpDeprecationInspection */
            $connection->exec('DROP TABLE payone_payment_card');
            /** @noinspection PhpDeprecationInspection */
            $connection->exec('DROP TABLE payone_payment_redirect');
            /** @noinspection PhpDeprecationInspection */
            $connection->exec('DROP TABLE payone_payment_mandate');
        }
    }

    private function getRuleInstallerSecureInvoice(): RuleInstallerSecureInvoice
    {
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');
        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->container->get('country.repository');
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->container->get('currency.repository');

        return new RuleInstallerSecureInvoice(
            $ruleRepository,
            $countryRepository,
            $currencyRepository
        );
    }

    private function getConfigInstaller(): ConfigInstaller
    {
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);

        return new ConfigInstaller($systemConfigService);
    }

    private function getPaymentMethodInstaller(): PaymentMethodInstaller
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $paymentMethodSalesChannelRepository */
        $paymentMethodSalesChannelRepository = $this->container->get('sales_channel_payment_method.repository');
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        return new PaymentMethodInstaller(
            $pluginIdProvider,
            $paymentMethodRepository,
            $salesChannelRepository,
            $paymentMethodSalesChannelRepository,
            $connection
        );
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        return new CustomFieldInstaller($customFieldSetRepository, $customFieldRepository);
    }
}
