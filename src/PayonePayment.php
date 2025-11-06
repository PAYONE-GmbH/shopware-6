<?php

declare(strict_types=1);

namespace PayonePayment;

use Doctrine\DBAL\Connection;
use PayonePayment\DependencyInjection\Compiler\PaymentMethodRegistryCompilerPass;
use PayonePayment\DependencyInjection\Compiler\RequestEnricherTestCompilerPass;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Installer\RuleInstaller\RuleInstallerSecureInvoice;
use PayonePayment\Provider\Alipay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
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
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Path;

class PayonePayment extends Plugin
{
    final public const PLUGIN_NAME = 'PayonePayment';

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator  = new FileLocator('Resources/config');
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        (new DelegatingLoader($resolver))->load(
            Path::join($this->getPath(), '/Resources/config/{packages}/*.yaml'),
            'glob',
        );

        $container->addCompilerPass(new PaymentMethodRegistryCompilerPass());
        $container->addCompilerPass(new RequestEnricherTestCompilerPass());

        $container->addCompilerPass(
            new MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\AmazonPay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\ApplePay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Bancontact\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Eps\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\GooglePay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\IDeal\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Klarna\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Paydirekt\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Payolution\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Payone\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\PayPal\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\PostFinance\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Przelewy24\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Ratepay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\SofortBanking\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\Trustly\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );

        $container->addCompilerPass(
            new Provider\WeChatPay\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPass(),
        );
    }

    #[\Override]
    public function install(InstallContext $installContext): void
    {
        $this->getConfigInstaller()->install($installContext);
        $this->getCustomFieldInstaller()->install($installContext);
        $this->getPaymentMethodInstaller()->install($installContext);
        $this->getRuleInstallerSecureInvoice()->install($installContext);
    }

    #[\Override]
    public function update(UpdateContext $updateContext): void
    {
        $this->getConfigInstaller()->update($updateContext);
        $this->getCustomFieldInstaller()->update($updateContext);
        $this->getPaymentMethodInstaller()->update($updateContext);
        $this->getRuleInstallerSecureInvoice()->update($updateContext);
    }

    #[\Override]
    public function postUpdate(UpdateContext $updateContext): void
    {
        $this->getCustomFieldInstaller()->cleanup($updateContext);
    }

    #[\Override]
    public function activate(ActivateContext $activateContext): void
    {
        $this->getConfigInstaller()->activate($activateContext);
        $this->getCustomFieldInstaller()->activate($activateContext);
        $this->getPaymentMethodInstaller()->activate($activateContext);
        $this->getRuleInstallerSecureInvoice()->activate($activateContext);
    }

    #[\Override]
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->getConfigInstaller()->deactivate($deactivateContext);
        $this->getCustomFieldInstaller()->deactivate($deactivateContext);
        $this->getPaymentMethodInstaller()->deactivate($deactivateContext);
        $this->getRuleInstallerSecureInvoice()->deactivate($deactivateContext);
    }

    #[\Override]
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$this->container instanceof ContainerInterface) {
            // symfony 6.4: variable has been set to ContainerInterface|null.
            throw new \RuntimeException('container instance missing.');
        }

        $this->getConfigInstaller()->uninstall($uninstallContext);
        $this->getCustomFieldInstaller()->uninstall($uninstallContext);
        $this->getPaymentMethodInstaller()->uninstall($uninstallContext);
        $this->getRuleInstallerSecureInvoice()->uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_card');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_redirect');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_mandate'); // legacy table. does not exist anymore.
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_notification_forward');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_notification_target');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_order_transaction_data');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_amazon_redirect');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_order_action_log');
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_webhook_log');
    }

    #[\Override]
    public function executeComposerCommands(): bool
    {
        return true;
    }

    private function getRuleInstallerSecureInvoice(): RuleInstallerSecureInvoice
    {
        if (!$this->container instanceof ContainerInterface) {
            // symfony 6.4: variable has been set to ContainerInterface|null.
            throw new \RuntimeException('container instance missing.');
        }

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
            $paymentMethodRepository,
        );
    }

    private function getConfigInstaller(): ConfigInstaller
    {
        if (!$this->container instanceof ContainerInterface) {
            // symfony 6.4: variable has been set to ContainerInterface|null.
            throw new \RuntimeException('container instance missing.');
        }

        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);

        return new ConfigInstaller($systemConfigService);
    }

    private function getPaymentMethodInstaller(): PaymentMethodInstaller
    {
        if (!$this->container instanceof ContainerInterface) {
            // symfony 6.4: variable has been set to ContainerInterface|null.
            throw new \RuntimeException('container instance missing.');
        }

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
        /** @var PaymentMethodDefinition $paymentMethodDefinition */
        $paymentMethodDefinition = $this->container->get(PaymentMethodDefinition::class);

        return new PaymentMethodInstaller(
            $pluginIdProvider,
            $paymentMethodRepository,
            $salesChannelRepository,
            $paymentMethodSalesChannelRepository,
            $connection,
            $paymentMethodDefinition,
        );
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        if (!$this->container instanceof ContainerInterface) {
            // symfony 6.4: variable has been set to ContainerInterface|null.
            throw new \RuntimeException('container instance missing.');
        }

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        return new CustomFieldInstaller($customFieldSetRepository, $customFieldRepository);
    }
}
