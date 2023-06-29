<?php

declare(strict_types=1);

namespace PayonePayment;

use Doctrine\DBAL\Connection;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Installer\RuleInstaller\RuleInstallerSecureInvoice;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
    final public const PLUGIN_NAME = 'PayonePayment';

    public function build(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');

        parent::build($container);
    }

    public function install(InstallContext $installContext): void
    {
        $this->getConfigInstaller()->install($installContext);
        $this->getCustomFieldInstaller()->install($installContext);
        $this->getPaymentMethodInstaller()->install($installContext);
        $this->getRuleInstallerSecureInvoice()->install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->getConfigInstaller()->update($updateContext);
        $this->getCustomFieldInstaller()->update($updateContext);
        $this->getPaymentMethodInstaller()->update($updateContext);
        $this->getRuleInstallerSecureInvoice()->update($updateContext);
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        $this->getCustomFieldInstaller()->cleanup($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->getConfigInstaller()->activate($activateContext);
        $this->getCustomFieldInstaller()->activate($activateContext);
        $this->getPaymentMethodInstaller()->activate($activateContext);
        $this->getRuleInstallerSecureInvoice()->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->getConfigInstaller()->deactivate($deactivateContext);
        $this->getCustomFieldInstaller()->deactivate($deactivateContext);
        $this->getPaymentMethodInstaller()->deactivate($deactivateContext);
        $this->getRuleInstallerSecureInvoice()->deactivate($deactivateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->getConfigInstaller()->uninstall($uninstallContext);
        $this->getCustomFieldInstaller()->uninstall($uninstallContext);
        $this->getPaymentMethodInstaller()->uninstall($uninstallContext);
        $this->getRuleInstallerSecureInvoice()->uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('DROP TABLE payone_payment_card');
        $connection->executeStatement('DROP TABLE payone_payment_redirect');
        $connection->executeStatement('DROP TABLE payone_payment_mandate');
        $connection->executeStatement('DROP TABLE payone_payment_notification_forward');
        $connection->executeStatement('DROP TABLE payone_payment_notification_target');
        $connection->executeStatement('DROP TABLE payone_payment_order_transaction_data');
    }

    private function getRuleInstallerSecureInvoice(): RuleInstallerSecureInvoice
    {
        /** @var EntityRepository $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');
        /** @var EntityRepository $countryRepository */
        $countryRepository = $this->container->get('country.repository');
        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->container->get('currency.repository');
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');

        return new RuleInstallerSecureInvoice(
            $ruleRepository,
            $countryRepository,
            $currencyRepository,
            $paymentMethodRepository
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
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepository $paymentMethodSalesChannelRepository */
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
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        return new CustomFieldInstaller($customFieldSetRepository, $customFieldRepository);
    }
}
