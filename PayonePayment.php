<?php

namespace PayonePayment;

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
    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        parent::install($context);
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        parent::update($context);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        parent::deactivate($context);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__));
        $loader->load('./DependencyInjection/services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return null;
    }
}
