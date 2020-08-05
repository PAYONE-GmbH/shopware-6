<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Doctrine\DBAL\Connection;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneEps;
use PayonePayment\PaymentMethod\PayoneIDeal;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayoneSofortBanking;
use PayonePayment\PayonePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodInstaller implements InstallerInterface
{
    public const PAYMENT_METHODS = [
        PayoneCreditCard::class,
        PayoneDebit::class,
        PayonePaypal::class,
        PayonePaypalExpress::class,
        PayonePayolutionInstallment::class,
        PayonePayolutionInvoicing::class,
        PayonePayolutionDebit::class,
        PayoneSofortBanking::class,
        PayoneEps::class,
        PayoneIDeal::class,
    ];

    /** @var PluginIdProvider */
    private $pluginIdProvider;

    /** @var EntityRepositoryInterface */
    private $paymentMethodRepository;

    /** @var EntityRepositoryInterface */
    private $salesChannelRepository;

    /** @var EntityRepositoryInterface */
    private $paymentMethodSalesChannelRepository;

    /** @var Connection */
    private $connection;

    public function __construct(ContainerInterface $container)
    {
        $this->pluginIdProvider                    = $container->get(PluginIdProvider::class);
        $this->paymentMethodRepository             = $container->get('payment_method.repository');
        $this->salesChannelRepository              = $container->get('sales_channel.repository');
        $this->paymentMethodSalesChannelRepository = $container->get('sales_channel_payment_method.repository');
        $this->connection                          = $container->get(Connection::class);
    }

    public function install(InstallContext $context): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->deactivatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    private function getPaymentMethods(): array
    {
        $paymentMethods = [];

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethods[] = new $paymentMethod();
        }

        return $paymentMethods;
    }

    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(PayonePayment::class, $context);

        $customFields = [
            CustomFieldInstaller::TEMPLATE  => $paymentMethod->getTemplate(),
            CustomFieldInstaller::IS_PAYONE => true,
        ];

        $data = [
            'id'                => $paymentMethod->getId(),
            'name'              => $paymentMethod->getName(),
            'description'       => $paymentMethod->getDescription(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'position'          => $paymentMethod->getPosition(),
            'pluginId'          => $pluginId,
            'customFields'      => $customFields,
            'translations'      => $paymentMethod->getTranslations(),
        ];

        $this->paymentMethodRepository->upsert([$data], $context);

        // TODO: This is a quite ugly workaround for the custom field translation problem here.
        // The custom fields are only set for the current language which results in non loading
        // checkout contents for other language contexts. We need a proper way to install the
        // custom fields for all languages but not only the current one.
        $customFields[CustomFieldInstaller::IS_PAYONE] = 1;
        $customFields                                  = json_encode($customFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->connection->exec("UPDATE `payment_method_translation` SET `custom_fields` = '{$customFields}' WHERE `custom_fields` IS NULL AND `payment_method_id` = UNHEX('{$paymentMethod->getId()}');");

        $channels = $this->salesChannelRepository->searchIds(new Criteria(), $context);

        foreach ($channels->getIds() as $channel) {
            $data = [
                'salesChannelId'  => $channel,
                'paymentMethodId' => $paymentMethod->getId(),
            ];

            $this->paymentMethodSalesChannelRepository->upsert([$data], $context);
        }
    }

    private function deactivatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id'     => $paymentMethod->getId(),
            'active' => false,
        ];

        $this->paymentMethodRepository->update([$data], $context);
    }
}
