<?php

declare(strict_types=1);

namespace PayonePayment;

use Doctrine\DBAL\Connection;
use PayonePayment\Installer\PaymentMethodInstaller;
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
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
        $loader->load('services.xml');
    }

    public function install(InstallContext $context): void
    {
        (new PaymentMethodInstaller($this->container))->install($context);
    }

    public function update(UpdateContext $context): void
    {
        (new PaymentMethodInstaller($this->container))->install($context);
    }

    public function activate(ActivateContext $context): void
    {
        (new PaymentMethodInstaller($this->container))->install($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        (new PaymentMethodInstaller($this->container))->install($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        (new PaymentMethodInstaller($this->container))->install($context);

        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        $connection->exec('DROP TABLE payone_payment_config');
    }
}
