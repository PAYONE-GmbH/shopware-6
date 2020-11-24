<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Doctrine\DBAL\Connection;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneEps;
use PayonePayment\PaymentMethod\PayoneIDeal;
use PayonePayment\PaymentMethod\PayonePaydirekt;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayonePrepayment;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\PaymentMethod\PayoneSofortBanking;
use PayonePayment\PaymentMethod\PayoneTrustly;
use PayonePayment\PayonePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
        PayonePaydirekt::class,
        PayonePrepayment::class,
        PayoneTrustly::class,
        PayoneSecureInvoice::class,
    ];

    public const AFTER_ORDER_PAYMENT_METHODS = [
        PayoneCreditCard::class,
        PayoneDebit::class,
        PayonePaypal::class,
        PayonePayolutionInstallment::class,
        PayonePayolutionInvoicing::class,
        PayonePayolutionDebit::class,
        PayoneSofortBanking::class,
        PayoneEps::class,
        PayoneIDeal::class,
        PayonePaydirekt::class,
        PayonePrepayment::class,
        PayoneTrustly::class,
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

            // Only do this within an install context otherwise this may
            // interfere badly with merchant configurations.
            $this->enablePaymentMethodForAllSalesChannels($paymentMethod, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        // Fix for usage of bad UUIDv4 value in https://github.com/PAYONE-GmbH/shopware-6/pull/65.
        // Todo: Remove this after some time has passed.
        // If we find a payment method entity with the concrete invalid UUIDv4 value we update the key
        // before any update procedures take place otherwise we would have a duplicate payment method.
        // This is also the reason why a migration is not a viable way here.
        if ($this->findPaymentMethodEntity('0b532088e2da3092f9f7054ec4009d18', $context->getContext())) {
            $this->connection->exec("UPDATE `payment_method` SET `id` = UNHEX('4e8a9d3d3c6e428887573856b38c9003') WHERE `id` = UNHEX('0b532088e2da3092f9f7054ec4009d18');");
            $this->connection->exec("UPDATE `sales_channel` SET `payment_method_ids` = REPLACE(`payment_method_ids`, '0b532088e2da3092f9f7054ec4009d18', '4e8a9d3d3c6e428887573856b38c9003');");
        }

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

    private function findPaymentMethodEntity(string $id, Context $context): ?PaymentMethodEntity
    {
        return $this->paymentMethodRepository
            ->search(new Criteria([$id]), $context)
            ->first();
    }

    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(PayonePayment::class, $context);

        // Collect some common data which will be used for both update and insert
        $data = [
            'id'                => $paymentMethod->getId(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'pluginId'          => $pluginId,
            'afterOrderEnabled' => in_array(get_class($paymentMethod), self::AFTER_ORDER_PAYMENT_METHODS),

            'customFields' => [
                CustomFieldInstaller::TEMPLATE  => $paymentMethod->getTemplate(),
                CustomFieldInstaller::IS_PAYONE => true,
            ],
        ];

        // Find existing payment method by ID for update / install decision
        $paymentMethodEntity = $this->findPaymentMethodEntity($paymentMethod->getId(), $context);

        // Decide whether to update an existing or install a new payment method
        if ($paymentMethodEntity instanceof PaymentMethodEntity) {
            $this->updatePaymentMethod($data, $context);
        } else {
            $this->installPaymentMethod($data, $paymentMethod, $context);
        }

        // Re-fetch payment method from database to operate on proper data
        $paymentMethodEntity = $this->findPaymentMethodEntity($paymentMethod->getId(), $context);

        if (!($paymentMethodEntity instanceof PaymentMethodEntity)) {
            // we are in a bad state here because the payment method must exist if everything went well
            // todo: find a better solution, for now just ignore this problem
            return;
        }

        $this->fixMissingCustomFieldsForTranslations($paymentMethod, $paymentMethodEntity);
    }

    private function installPaymentMethod(array $data, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = array_merge($data, [
            'name'         => $paymentMethod->getName(),
            'description'  => $paymentMethod->getDescription(),
            'position'     => $paymentMethod->getPosition(),
            'translations' => $paymentMethod->getTranslations(),
        ]);

        $this->paymentMethodRepository->create([$data], $context);
    }

    private function updatePaymentMethod(array $data, Context $context): void
    {
        $this->paymentMethodRepository->update([$data], $context);
    }

    private function enablePaymentMethodForAllSalesChannels(PaymentMethodInterface $paymentMethod, Context $context): void
    {
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

        $paymentMethodExists = $this->paymentMethodExists($data, $context);

        if ($paymentMethodExists === false) {
            return;
        }

        $this->paymentMethodRepository->update([$data], $context);
    }

    private function paymentMethodExists(array $data, Context $context): bool
    {
        if (empty($data['id'])) {
            return false;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $data['id']));

        $result = $this->paymentMethodRepository->search($criteria, $context);

        if ($result->getTotal() === 0) {
            return false;
        }

        return true;
    }

    private function fixMissingCustomFieldsForTranslations(PaymentMethodInterface $paymentMethod, PaymentMethodEntity $paymentMethodEntity): void
    {
        $customFields = $paymentMethodEntity->getCustomFields() ?? [];

        // Prepare custom fields for JSON encoding
        $customFields[CustomFieldInstaller::IS_PAYONE] = 1;

        // TODO: This is a quite ugly workaround for the custom field translation problem here.
        // The custom fields are only set for the current language which results in non loading
        // checkout contents for other language contexts. We need a proper way to install the
        // custom fields for all languages but not only the current one.
        $customFields = json_encode($customFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->connection->exec(sprintf("
            UPDATE `payment_method_translation`
            SET
                `custom_fields` = '%s'
            WHERE
                `custom_fields` IS NULL AND
                `payment_method_id` = UNHEX('%s');
         ", $customFields, $paymentMethod->getId()));
    }
}
