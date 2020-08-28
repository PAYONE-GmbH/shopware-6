<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\RuleInstaller;

use PayonePayment\Installer\InstallerInterface;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RuleInstallerSecureInvoice implements InstallerInterface
{
    public const VALID_COUNTRIES = [
        'AT',
        'CH',
        'DE',
    ];

    public const CURRENCIES = [
        'EUR',
    ];

    private const RULE_ID               = 'bf54529febf323ec7d27256b178207f5';
    private const CONDITION_ID_COUNTRY  = '23a2158b05a93ddd4a0799074846607c';
    private const CONDITION_ID_CURRENCY = '6099e1e292f737aa31c126a73339c92e';

    /** @var EntityRepositoryInterface */
    private $ruleRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->ruleRepository    = $container->get('rule.repository');
        $this->countryRepository = $container->get('country.repository');
    }

    public function install(InstallContext $context): void
    {
        $this->upsertAvailabilityRule($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->upsertAvailabilityRule($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->removeAvailabilityRule($context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    private function upsertAvailabilityRule(Context $context): void
    {
        $data = [
            'id'          => self::RULE_ID,
            'name'        => 'Payone secure invoice',
            'priority'    => 1,
            'description' => 'Determines whether or not Payone secure invoice payment is available.',
            'conditions'  => [
                [
                    'id'    => self::CONDITION_ID_COUNTRY,
                    'type'  => (new BillingCountryRule())->getName(),
                    'value' => [
                        'operator'   => BillingCountryRule::OPERATOR_EQ,
                        'countryIds' => array_values($this->getCountries($context)),
                    ],
                ],
                [
                    'id'    => self::CONDITION_ID_CURRENCY,
                    'type'  => null,
                    'value' => [
                        'operator' => '=',
                        'currency' => $this->getCurrency($context),
                    ],
                ],
            ],
            'paymentMethods' => [
                ['id' => PayoneSecureInvoice::UUID],
            ],
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->ruleRepository->upsert([$data], $context);
        });
    }

    private function removeAvailabilityRule(Context $context): void
    {
        $deletion = [
            'id' => self::RULE_ID,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($deletion): void {
            $this->ruleRepository->delete([$deletion], $context);
        });
    }

    private function getCountries(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('iso', self::VALID_COUNTRIES)
        );

        return $this->countryRepository->search($criteria, $context)->getIds();
    }

    private function getCurrency(Context $context): string
    {
        return 'EUR';
    }
}
