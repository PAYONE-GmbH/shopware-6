<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Doctrine\DBAL\Connection;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PayoneAlipay;
use PayonePayment\PaymentMethod\PayoneApplePay;
use PayonePayment\PaymentMethod\PayoneBancontact;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneEps;
use PayonePayment\PaymentMethod\PayoneIDeal;
use PayonePayment\PaymentMethod\PayoneKlarnaDirectDebit;
use PayonePayment\PaymentMethod\PayoneKlarnaInstallment;
use PayonePayment\PaymentMethod\PayoneKlarnaInvoice;
use PayonePayment\PaymentMethod\PayoneOpenInvoice;
use PayonePayment\PaymentMethod\PayonePaydirekt;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayonePostfinanceCard;
use PayonePayment\PaymentMethod\PayonePostfinanceWallet;
use PayonePayment\PaymentMethod\PayonePrepayment;
use PayonePayment\PaymentMethod\PayonePrzelewy24;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\PaymentMethod\PayoneSecuredDirectDebit;
use PayonePayment\PaymentMethod\PayoneSecuredInstallment;
use PayonePayment\PaymentMethod\PayoneSecuredInvoice;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use PayonePayment\PaymentMethod\PayoneSofortBanking;
use PayonePayment\PaymentMethod\PayoneTrustly;
use PayonePayment\PaymentMethod\PayoneWeChatPay;
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

class PaymentMethodInstaller implements InstallerInterface
{
    public const PAYMENT_METHOD_IDS = [
        PayoneApplePay::class => '4cbc89a06e544c06b413a41d158f5e00',
        PayoneCreditCard::class => '37f90a48d9194762977c9e6db36334e0',
        PayoneDebit::class => '1b017bef157b4222b734659361d996fd',
        PayonePaypal::class => '21e157163fdb4aa4862a2109abcd7522',
        PayonePaypalExpress::class => '5ddf648859a84396a98c97a1a92c107f',
        PayonePayolutionInstallment::class => '569b46970ad2458ca8f17f1ebb754137',
        PayonePayolutionInvoicing::class => '0407fd0a5c4b4d2bafc88379efe8cf8d',
        PayonePayolutionDebit::class => '700954775fad4a8f92463b3d629c8ad5',
        PayoneSofortBanking::class => '9022c4733d14411e84a78707088487aa',
        PayoneEps::class => '6004c8b082234ba5b2834da9874c5ec7',
        PayoneIDeal::class => '3f567ad46f1947e3960b66ed3af537aa',
        PayonePaydirekt::class => 'b5b52a27e6b14a37bbb4087ec821b0f4',
        PayonePrepayment::class => '267699739afd4cdd9663cac0bd269da6',
        PayoneTrustly::class => '741f1deec67d4012bd3ccce265b2e15e',
        PayoneSecureInvoice::class => '4e8a9d3d3c6e428887573856b38c9003',
        PayoneOpenInvoice::class => '9024aa5a502b4544a745b6b64b486e21',
        PayoneBancontact::class => '32ecec740c7142c9bf51d00ea894ffad',
        PayoneRatepayDebit::class => '48f2034b3c62480a8554781cf9cac574',
        PayoneRatepayInstallment::class => '0af0f201fd164ca9ae72313c70201d18',
        PayoneRatepayInvoicing::class => '240dcc8bf5fc409c9dcf840698c082aa',
        PayoneKlarnaInvoice::class => 'c4cd059611cc4d049187d8d955ec1f91',
        PayoneKlarnaDirectDebit::class => '31af2cbeda5242bfbfe4531e203f8a42',
        PayoneKlarnaInstallment::class => 'a18ffddd4baf4948b8c9f9d3d8abd2d4',
        PayonePrzelewy24::class => '6068e01cef8b4c9698956c6cca648d50',
        PayoneWeChatPay::class => 'e9647d765b284cea9c4c0d68005665b7',
        PayonePostfinanceCard::class => '8b4503f88a7746069a670e1689908832',
        PayonePostfinanceWallet::class => 'cd65c7f9c0cc4e0886799f7cc7407494',
        PayoneAlipay::class => 'fef3c750f8e94a6abb7d0a8061ac9faf',
        PayoneSecuredInvoice::class => '4ca01ac1471c4da5b76faeaa42524cc3',
        PayoneSecuredInstallment::class => '9c4d04f6ad4b4a2787e3812c56b6153b',
        PayoneSecuredDirectDebit::class => '72c4c88b918441848e20081de67a16c4',
    ];

    public const HANDLER_IDENTIFIER_ROOT_NAMESPACE = 'PayonePayment';

    public const PAYMENT_METHODS = [
        PayoneApplePay::class,
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
        PayoneOpenInvoice::class,
        PayoneBancontact::class,
        PayoneRatepayDebit::class,
        PayoneRatepayInstallment::class,
        PayoneRatepayInvoicing::class,
        PayoneKlarnaInvoice::class,
        PayoneKlarnaDirectDebit::class,
        PayoneKlarnaInstallment::class,
        PayonePrzelewy24::class,
        PayoneWeChatPay::class,
        PayonePostfinanceCard::class,
        PayonePostfinanceWallet::class,
        PayoneAlipay::class,
        PayoneSecuredInvoice::class,
        PayoneSecuredInstallment::class,
        PayoneSecuredDirectDebit::class,
    ];

    public const AFTER_ORDER_PAYMENT_METHODS = [
        PayoneApplePay::class,
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
        PayoneRatepayDebit::class,
        PayoneRatepayInstallment::class,
        PayoneRatepayInvoicing::class,
        PayoneKlarnaInvoice::class,
        PayoneKlarnaDirectDebit::class,
        PayoneKlarnaInstallment::class,
        PayonePostfinanceCard::class,
        PayonePostfinanceWallet::class,
    ];

    private PluginIdProvider $pluginIdProvider;

    private EntityRepositoryInterface $paymentMethodRepository;

    private EntityRepositoryInterface $salesChannelRepository;

    private EntityRepositoryInterface $paymentMethodSalesChannelRepository;

    private Connection $connection;

    public function __construct(
        PluginIdProvider $pluginIdProvider,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $paymentMethodSalesChannelRepository,
        Connection $connection
    ) {
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->paymentMethodSalesChannelRepository = $paymentMethodSalesChannelRepository;
        $this->connection = $connection;
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
            if (method_exists($this->connection, 'executeStatement')) {
                $this->connection->executeStatement("UPDATE `payment_method` SET `id` = UNHEX('4e8a9d3d3c6e428887573856b38c9003') WHERE `id` = UNHEX('0b532088e2da3092f9f7054ec4009d18');");
                $this->connection->executeStatement("UPDATE `sales_channel` SET `payment_method_ids` = REPLACE(`payment_method_ids`, '0b532088e2da3092f9f7054ec4009d18', '4e8a9d3d3c6e428887573856b38c9003');");
            } elseif (method_exists($this->connection, 'exec')) {
                /** @noinspection PhpDeprecationInspection */
                $this->connection->exec("UPDATE `payment_method` SET `id` = UNHEX('4e8a9d3d3c6e428887573856b38c9003') WHERE `id` = UNHEX('0b532088e2da3092f9f7054ec4009d18');");
                /** @noinspection PhpDeprecationInspection */
                $this->connection->exec("UPDATE `sales_channel` SET `payment_method_ids` = REPLACE(`payment_method_ids`, '0b532088e2da3092f9f7054ec4009d18', '4e8a9d3d3c6e428887573856b38c9003');");
            }
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
            'id' => $paymentMethod->getId(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'pluginId' => $pluginId,
            'afterOrderEnabled' => \in_array(\get_class($paymentMethod), self::AFTER_ORDER_PAYMENT_METHODS, true),
        ];

        // Find existing payment method by ID for update / install decision
        $paymentMethodEntity = $this->findPaymentMethodEntity($paymentMethod->getId(), $context);

        // Decide whether to update an existing or install a new payment method
        if ($paymentMethodEntity instanceof PaymentMethodEntity) {
            $this->updatePaymentMethod($data, $context);
        } else {
            $this->installPaymentMethod($data, $paymentMethod, $context);
        }
    }

    private function installPaymentMethod(array $data, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = array_merge($data, [
            'name' => $paymentMethod->getName(),
            'description' => $paymentMethod->getDescription(),
            'position' => $paymentMethod->getPosition(),
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
                'salesChannelId' => $channel,
                'paymentMethodId' => $paymentMethod->getId(),
            ];

            $this->paymentMethodSalesChannelRepository->upsert([$data], $context);
        }
    }

    private function deactivatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id' => $paymentMethod->getId(),
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
}
