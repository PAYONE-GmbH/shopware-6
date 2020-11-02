<?php

declare(strict_types=1);

namespace PayonePayment;

use Doctrine\DBAL\Connection;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Installer\RuleInstaller\RuleInstallerSecureInvoice;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class PayonePayment extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');

        parent::build($container);
    }

    public function install(InstallContext $context): void
    {
        (new ConfigInstaller($this->container))->install($context);
        (new CustomFieldInstaller($this->container))->install($context);
        (new PaymentMethodInstaller($this->container))->install($context);
        (new RuleInstallerSecureInvoice($this->container))->install($context);
    }

    public function update(UpdateContext $context): void
    {
        (new ConfigInstaller($this->container))->update($context);
        (new CustomFieldInstaller($this->container))->update($context);
        (new PaymentMethodInstaller($this->container))->update($context);
        (new RuleInstallerSecureInvoice($this->container))->update($context);
    }

    public function activate(ActivateContext $context): void
    {
        (new ConfigInstaller($this->container))->activate($context);
        (new CustomFieldInstaller($this->container))->activate($context);
        (new PaymentMethodInstaller($this->container))->activate($context);
        (new RuleInstallerSecureInvoice($this->container))->activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        (new ConfigInstaller($this->container))->deactivate($context);
        (new CustomFieldInstaller($this->container))->deactivate($context);
        (new PaymentMethodInstaller($this->container))->deactivate($context);
        (new RuleInstallerSecureInvoice($this->container))->deactivate($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        (new ConfigInstaller($this->container))->uninstall($context);
        (new CustomFieldInstaller($this->container))->uninstall($context);
        (new PaymentMethodInstaller($this->container))->uninstall($context);
        (new RuleInstallerSecureInvoice($this->container))->uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->exec('DROP TABLE payone_payment_card');
        $connection->exec('DROP TABLE payone_payment_redirect');
        $connection->exec('DROP TABLE payone_payment_mandate');
    }
}
