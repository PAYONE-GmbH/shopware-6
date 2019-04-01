<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayoneSofort;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Helper\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodInstaller implements InstallerInterface
{
    /** @var PluginIdProvider */
    private $pluginIdProvider;

    /** @var EntityRepositoryInterface */
    private $paymentMethodRepository;

    /** @var PaymentMethodInterface[] */
    private $paymentMethods;

    public function __construct(ContainerInterface $container)
    {
        $this->pluginIdProvider        = $container->get(PluginIdProvider::class);
        $this->paymentMethodRepository = $container->get('payment_method.repository');

        $this->paymentMethods = [
            new PayoneCreditCard(),
            new PayoneDebit(),
            new PayonePaypal(),
            new PayoneSofort(),
        ];
    }

    public function install(InstallContext $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->deactivatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->activatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    public function deactivate(DeactivateContext $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->deactivatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByTechnicalName('PayonePayment', $context);

        $data = [
            'id'                => $paymentMethod->getId(),
            'technicalName'     => $paymentMethod->getTechnicalName(),
            'name'              => $paymentMethod->getName(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'pluginId'          => $pluginId,
        ];

        $this->paymentMethodRepository->upsert([$data], $context);
    }
    
    private function activatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id'     => $paymentMethod->getId(),
            'active' => true,
        ];

        $this->paymentMethodRepository->update([$data], $context);
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
