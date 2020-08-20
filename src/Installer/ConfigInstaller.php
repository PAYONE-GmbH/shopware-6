<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigInstaller implements InstallerInterface
{
    private const DEFAULT_VALUES = [
        'transactionMode' => 'test',

        // Default authorization modes for payment methods
        'creditCardAuthorizationMethod'            => 'preauthorization',
        'debitAuthorizationMethod'                 => 'authorization',
        'payolutionDebitAuthorizationMethod'       => 'preauthorization',
        'payolutionInstallmentAuthorizationMethod' => 'authorization',
        'payolutionInvoicingAuthorizationMethod'   => 'preauthorization',
        'paypalAuthorizationMethod'                => 'preauthorization',
        'paypalExpressAuthorizationMethod'         => 'preauthorization',
        'sofortAuthorizationMethod'                => 'authorization',

        // Default payment status mapping
        'paymentStatusAppointed'      => StateMachineTransitionActions::ACTION_REOPEN,
        'paymentStatusCapture'        => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusPartialCapture' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusPaid'           => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusUnderpaid'      => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusCancelation'    => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusRefund'         => StateMachineTransitionActions::ACTION_REFUND,
        'paymentStatusPartialRefund'  => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
        'paymentStatusDebit'          => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusReminder'       => StateMachineTransitionActions::ACTION_REMIND,
        'paymentStatusVauthorization' => '',
        'paymentStatusVsettlement'    => '',
        'paymentStatusTransfer'       => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusInvoice'        => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusFailed'         => StateMachineTransitionActions::ACTION_CANCEL,
    ];

    private const UPDATE_VALUES = [ // Updated for 6.2
        'paymentStatusCapture'        => [StateMachineTransitionActions::ACTION_PAY => StateMachineTransitionActions::ACTION_PAID],
        'paymentStatusPartialCapture' => [StateMachineTransitionActions::ACTION_PAY_PARTIALLY => StateMachineTransitionActions::ACTION_PAID_PARTIALLY],
        'paymentStatusPaid'           => [StateMachineTransitionActions::ACTION_PAY => StateMachineTransitionActions::ACTION_PAID],
        'paymentStatusUnderpaid'      => [StateMachineTransitionActions::ACTION_PAY_PARTIALLY => StateMachineTransitionActions::ACTION_PAID_PARTIALLY],
        'paymentStatusDebit'          => [StateMachineTransitionActions::ACTION_PAY => StateMachineTransitionActions::ACTION_PAID],
        'paymentStatusInvoice'        => [StateMachineTransitionActions::ACTION_PAY => StateMachineTransitionActions::ACTION_PAID],
    ];

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(ContainerInterface $container)
    {
        $this->systemConfigService = $container->get(SystemConfigService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        if (empty(self::DEFAULT_VALUES)) {
            return;
        }

        $this->setDefaultValues($context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        if (empty(self::DEFAULT_VALUES)) {
            return;
        }

        $this->setDefaultValues($context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function setDefaultValues(Context $context)
    {
        $domain = 'PayonePayment.settings.';

        foreach (self::DEFAULT_VALUES as $key => $value) {
            $configKey = $domain . $key;

            $currentValue = $this->systemConfigService->get($configKey);

            if ($currentValue !== null) {
                continue;
            }

            $this->systemConfigService->set($configKey, $value);
        }

        foreach (self::UPDATE_VALUES as $key => $values) {
            foreach ($values as $from => $to) {
                $configKey = $domain . $key;

                $currentValue = $this->systemConfigService->get($configKey);

                if ($currentValue !== null && $currentValue !== $from) {
                    continue;
                }

                $this->systemConfigService->set($configKey, $to);
            }
        }
    }
}
