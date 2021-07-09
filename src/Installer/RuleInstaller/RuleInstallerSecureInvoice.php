<?php

declare(strict_types=1);

namespace PayonePayment\Installer\RuleInstaller;

use PayonePayment\Installer\InstallerInterface;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Checkout\Customer\Rule\DifferentAddressesRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Currency\Rule\CurrencyRule;

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

    private const RULE_ID                          = 'bf54529febf323ec7d27256b178207f5';
    private const CONDITION_ID_AND                 = 'f37b1995a4714d0a88249c3e3aa52794';
    private const CONDITION_ID_COUNTRY             = '23a2158b05a93ddd4a0799074846607c';
    private const CONDITION_ID_CURRENCY            = '6099e1e292f737aa31c126a73339c92e';
    private const CONDITION_ID_DIFFERENT_ADDRESSES = 'f1a5251ffcd09b5dc0befc059dfad9c1';

    /** @var EntityRepositoryInterface */
    private $ruleRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(
        EntityRepositoryInterface $ruleRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->ruleRepository     = $ruleRepository;
        $this->countryRepository  = $countryRepository;
        $this->currencyRepository = $currencyRepository;
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
                    'id'       => self::CONDITION_ID_AND,
                    'type'     => (new AndRule())->getName(),
                    'children' => [
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
                            'type'  => (new CurrencyRule())->getName(),
                            'value' => [
                                'operator'    => CurrencyRule::OPERATOR_EQ,
                                'currencyIds' => array_values($this->getCurrencyIds($context)),
                            ],
                        ],
                        [
                            'id'    => self::CONDITION_ID_DIFFERENT_ADDRESSES,
                            'type'  => (new DifferentAddressesRule())->getName(),
                            'value' => [
                                'isDifferent' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'paymentMethods' => [
                ['id' => PayoneSecureInvoice::UUID],
            ],
        ];

        $context->scope(
            Context::SYSTEM_SCOPE,
            function (Context $context) use ($data): void {
                $this->ruleRepository->upsert([$data], $context);
            }
        );
    }

    private function removeAvailabilityRule(Context $context): void
    {
        $deletion = [
            'id' => self::RULE_ID,
        ];

        $context->scope(
            Context::SYSTEM_SCOPE,
            function (Context $context) use ($deletion): void {
                $this->ruleRepository->delete([$deletion], $context);
            }
        );
    }

    private function getCountries(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('iso', self::VALID_COUNTRIES)
        );

        return $this->countryRepository->search($criteria, $context)->getIds();
    }

    private function getCurrencyIds(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('isoCode', self::CURRENCIES)
        );

        return $this->currencyRepository->search($criteria, $context)->getIds();
    }
}
