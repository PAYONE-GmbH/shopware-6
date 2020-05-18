<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
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
        'paymentStatusCapture'        => StateMachineTransitionActions::ACTION_PAY,
        'paymentStatusPartialCapture' => StateMachineTransitionActions::ACTION_PAY_PARTIALLY,
        'paymentStatusPaid'           => StateMachineTransitionActions::ACTION_PAY,
        'paymentStatusUnderpaid'      => StateMachineTransitionActions::ACTION_PAY_PARTIALLY,
        'paymentStatusCancelation'    => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusRefund'         => StateMachineTransitionActions::ACTION_REFUND,
        'paymentStatusPartialRefund'  => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
        'paymentStatusDebit'          => StateMachineTransitionActions::ACTION_PAY,
        'paymentStatusReminder'       => StateMachineTransitionActions::ACTION_REMIND,
        'paymentStatusVauthorization' => '',
        'paymentStatusVsettlement'    => '',
        'paymentStatusTransfer'       => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusInvoice'        => StateMachineTransitionActions::ACTION_PAY,
        'paymentStatusFailed'         => StateMachineTransitionActions::ACTION_CANCEL,
    ];

    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var EntityRepositoryInterface */
    private $transitionRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->systemConfigService  = $container->get(SystemConfigService::class);
        $this->transitionRepository = $container->get('state_machine_transition.repository');
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

            if (strpos($key, 'paymentStatus')) {
                $transitionCriteria = new Criteria();
                $transitionCriteria->addAssociation('state_machine');
                $transitionCriteria->addFilter(new EqualsFilter('actionName', $value));
                $transitionCriteria->addFilter(new EqualsFilter('technicalName', 'order_transaction.state'));

                /** @var StateMachineTransitionEntity $searchResult */
                $searchResult = $this->transitionRepository->search($transitionCriteria, $context)->first();
                $value        = $searchResult->getId();
            }

            $this->systemConfigService->set($configKey, $value);
        }
    }
}
