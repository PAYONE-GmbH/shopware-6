<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Doctrine\DBAL\Connection;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PayonePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\Filesystem\Path;

class PaymentMethodInstaller implements InstallerInterface
{
    final public const HANDLER_IDENTIFIER_ROOT_NAMESPACE = 'PayonePayment';

    /**
     * @var list<class-string<PaymentMethodInterface>>
     */
    private static array $cache = [];

    /**
     * @psalm-type InstalledPayonePaymentMethodTableRow = array{
     *     handler_identifier: class-string,
     *     after_order_enabled: '0'|'1',
     *     technical_name: non-empty-string
     * }
     *
     * @var array<string, InstalledPayonePaymentMethodTableRow>|null $payments
     */
    private array|null $installedPaymentMethods = null;

    public function __construct(
        private readonly PluginIdProvider $pluginIdProvider,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $paymentMethodSalesChannelRepository,
        private readonly Connection $connection,
        private readonly PaymentMethodDefinition $paymentMethodDefinition,
    ) {
        if ([] !== self::$cache) {
            return;
        }

        $configurationFile    = Path::join(__DIR__, '../Resources/config/payment_methods.php');
        $payonePaymentMethods = [];

        if (\is_file($configurationFile) && \is_readable($configurationFile)) {
            /**
             * @noinspection UsingInclusionOnceReturnValueInspection
             * @var list<class-string<PaymentMethodInterface>> $payonePaymentMethods
             */
            $payonePaymentMethods = (array) require_once $configurationFile;
        }

        self::$cache = $payonePaymentMethods;
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
        if ($this->findPaymentMethodEntity('0b532088e2da3092f9f7054ec4009d18')) {
            $this->connection->executeStatement(<<<'SQL'
UPDATE `payment_method` SET `id` = UNHEX('4e8a9d3d3c6e428887573856b38c9003') WHERE `id` = UNHEX('0b532088e2da3092f9f7054ec4009d18');
SQL);
            $this->connection->executeStatement(<<<'SQL'
UPDATE `sales_channel` SET `payment_method_ids` = REPLACE(`payment_method_ids`, '0b532088e2da3092f9f7054ec4009d18', '4e8a9d3d3c6e428887573856b38c9003');
SQL);
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

    /**
     * @return list<PaymentMethodInterface>
     */
    private function getPaymentMethods(): array
    {
        $paymentMethods = [];

        foreach (self::$cache as $paymentMethodClass) {
            $paymentMethods[] = new $paymentMethodClass();
        }

        return $paymentMethods;
    }

    /**
     * Reads the payone payment method with te given ID from the database usind the connection.
     * The reason for not using DAL is that Shopware has registered a subscriber on
     * `PaymentEvents::PAYMENT_METHOD_LOADED_EVENT`, which causes problems on PaymentHandler class changes.
     */
    private function findPaymentMethodEntity(string $id): bool
    {
        $sql = <<<SQL
SELECT `technical_name` FROM `payment_method` WHERE HEX(`id`) = ?;
SQL;

        return [] !== $this->connection->executeQuery($sql, [ $id ])->fetchAllAssociative();
    }

    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $pluginId        = $this->pluginIdProvider->getPluginIdByBaseClass(PayonePayment::class, $context);
        $installed       = $this->getInstalledPaymentMethods($pluginId);
        $paymentMethodId = $paymentMethod::getId();

        // Collect some common data which will be used for both update and insert
        $data = [
            'id'                => $paymentMethodId,
            'handlerIdentifier' => $paymentMethod->getPaymentHandlerClassName(),
            'pluginId'          => $pluginId,
            'afterOrderEnabled' => $paymentMethod->isAfterOrderPayment(),
        ];

        if ($this->paymentMethodDefinition->getField('technicalName') instanceof Field) {
            $data['technicalName'] = $paymentMethod::getTechnicalName();
        }

        if (!isset($installed[$paymentMethodId])) {
            $this->installPaymentMethod($data, $paymentMethod, $context);

            return;
        }

        $installedPaymentMethod = $installed[$paymentMethodId];
        if (
            $data['handlerIdentifier'] !== $installedPaymentMethod['handler_identifier']
            || $data['afterOrderEnabled'] !== $installedPaymentMethod['after_order_enabled']
            || (isset($data['technicalName']) && $data['technicalName'] !== $installedPaymentMethod['technical_name'])
        ) {
            $this->updatePaymentMethod($data, $context);
        }
    }

    /**
     * Reads the installed payone payment methods from the database usind the connection.
     * The reason for not using DAL is that Shopware has registered a subscriber on
     * `PaymentEvents::PAYMENT_METHOD_LOADED_EVENT`, which causes problems on PaymentHandler class changes.
     */
    private function getInstalledPaymentMethods(string $pluginId)
    {
        if (null === $this->installedPaymentMethods) {
            $sql = <<<SQL
SELECT LOWER(HEX(`id`)) as id, `handler_identifier`, `after_order_enabled`, `technical_name`
FROM `payment_method`
WHERE HEX(`plugin_id`) = ?;
SQL;

            $this->installedPaymentMethods = $this->connection->executeQuery($sql, [ $pluginId ])->fetchAllAssociativeIndexed();
        }

        return $this->installedPaymentMethods;
    }

    private function installPaymentMethod(array $data, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = \array_merge($data, [
            'name'         => $paymentMethod->getName(),
            'description'  => $paymentMethod->getDescription(),
            'position'     => $paymentMethod->getPosition(),
            'translations' => $paymentMethod->getTranslations(),
        ]);

        $this->paymentMethodRepository->create([ $data ], $context);
    }

    private function updatePaymentMethod(array $data, Context $context): void
    {
        $localContext = clone $context;

        $localContext->addState(EntityIndexerRegistry::DISABLE_INDEXING);

        $this->paymentMethodRepository->update([ $data ], $localContext);
    }

    private function enablePaymentMethodForAllSalesChannels(
        PaymentMethodInterface $paymentMethod,
        Context $context,
    ): void {
        $channels = $this->salesChannelRepository->searchIds(new Criteria(), $context);

        foreach ($channels->getIds() as $channel) {
            $data = [
                'salesChannelId'  => $channel,
                'paymentMethodId' => $paymentMethod::getId(),
            ];

            $this->paymentMethodSalesChannelRepository->upsert([ $data ], $context);
        }
    }

    private function deactivatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id'     => $paymentMethod::getId(),
            'active' => false,
        ];

        $paymentMethodExists = $this->paymentMethodExists($data, $context);

        if (false === $paymentMethodExists) {
            return;
        }

        $this->paymentMethodRepository->update([ $data ], $context);
    }

    private function paymentMethodExists(array $data, Context $context): bool
    {
        if (empty($data['id'])) {
            return false;
        }

        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('id', $data['id']));

        return 0 !== $this->paymentMethodRepository->search($criteria, $context)->getTotal();
    }
}
