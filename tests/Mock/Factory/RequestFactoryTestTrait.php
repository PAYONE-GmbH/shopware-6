<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\RequestParameter\Builder\CustomerRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\SystemRequestParameterBuilder;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\HttpFoundation\RequestStack;

trait RequestFactoryTestTrait
{
    protected function getSystemRequest(): SystemRequest
    {
        $pluginService = $this->createMock(PluginService::class);
        $pluginEntity  = new PluginEntity();
        $pluginEntity->setVersion('1');
        $pluginService->method('getPluginByName')->willReturn($pluginEntity);

        return new SystemRequest(new ConfigReaderMock([]), $pluginService, '1.0.0-test');
    }

    protected function getSystemRequestBuilder(): SystemRequestParameterBuilder
    {
        $pluginService = $this->createMock(PluginService::class);
        $pluginEntity  = new PluginEntity();
        $pluginEntity->setVersion('1');
        $pluginService->method('getPluginByName')->willReturn($pluginEntity);

        $builder = new SystemRequestParameterBuilder($pluginService, '1.0.0-test');
        $builder->setCommonDependencies($this->createMock(RedirectHandler::class), $this->createMock(EntityRepositoryInterface::class), new ConfigReaderMock([]));

        return $builder;
    }

    protected function getGeneralTransactionRequestBuilder(): GeneralTransactionRequestParameterBuilder
    {
        return new GeneralTransactionRequestParameterBuilder(new CartHasher());
    }

    protected function getCurrencyEntity(): CurrencyEntity
    {
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode(Constants::CURRENCY_ISO);
        $currencyEntity->setItemRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, false));

        return $currencyEntity;
    }

    protected function getCurrencySearchResult(Context $context): EntitySearchResult
    {
        try {
            $entitySearchResult = new EntitySearchResult(
                CurrencyEntity::class,
                1,
                new EntityCollection([
                    $this->getCurrencyEntity(),
                ]),
                null,
                new Criteria(),
                $context
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$this->getCurrencyEntity()]), null, new Criteria(), Context::createDefaultContext());
        }

        return $entitySearchResult;
    }

    private function getCustomerRequest(): CustomerRequest
    {
        $languageRepository = $this->createMock(EntityRepository::class);
        $languageEntity     = new LanguageEntity();
        $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
        $localeEntity = new LocaleEntity();
        $localeEntity->setCode('de_DE');
        $languageEntity->setLocale($localeEntity);

        try {
            $entitySearchResult = new EntitySearchResult(
                LanguageEntity::class,
                1,
                new EntityCollection([$languageEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$languageEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $languageRepository->method('search')->willReturn($entitySearchResult);

        $salutationRepository = $this->createMock(EntityRepository::class);
        $salutationEntity     = new SalutationEntity();
        $salutationEntity->setId(Constants::SALUTATION_ID);

        try {
            $entitySearchResult = new EntitySearchResult(
                SalutationEntity::class,
                1,
                new EntityCollection([$salutationEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$salutationEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $salutationRepository->method('search')->willReturn($entitySearchResult);

        $countryRepository = $this->createMock(EntityRepository::class);
        $countryEntity     = new CountryEntity();
        $countryEntity->setId(Constants::COUNTRY_ID);

        try {
            $entitySearchResult = new EntitySearchResult(
                CountryEntity::class,
                1,
                new EntityCollection([$countryEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$countryEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $countryRepository->method('search')->willReturn($entitySearchResult);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        return new CustomerRequest($languageRepository, $salutationRepository, $countryRepository, $requestStack);
    }

    private function getCustomerRequestBuilder(): CustomerRequestParameterBuilder
    {
        $languageRepository = $this->createMock(EntityRepository::class);
        $languageEntity     = new LanguageEntity();
        $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
        $localeEntity = new LocaleEntity();
        $localeEntity->setCode('de_DE');
        $languageEntity->setLocale($localeEntity);

        try {
            $entitySearchResult = new EntitySearchResult(
                LanguageEntity::class,
                1,
                new EntityCollection([$languageEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$languageEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $languageRepository->method('search')->willReturn($entitySearchResult);

        $salutationRepository = $this->createMock(EntityRepository::class);
        $salutationEntity     = new SalutationEntity();
        $salutationEntity->setId(Constants::SALUTATION_ID);

        try {
            $entitySearchResult = new EntitySearchResult(
                SalutationEntity::class,
                1,
                new EntityCollection([$salutationEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$salutationEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $salutationRepository->method('search')->willReturn($entitySearchResult);

        $countryRepository = $this->createMock(EntityRepository::class);
        $countryEntity     = new CountryEntity();
        $countryEntity->setId(Constants::COUNTRY_ID);

        try {
            $entitySearchResult = new EntitySearchResult(
                CountryEntity::class,
                1,
                new EntityCollection([$countryEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$countryEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $countryRepository->method('search')->willReturn($entitySearchResult);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        return new CustomerRequestParameterBuilder($languageRepository, $salutationRepository, $countryRepository, $requestStack);
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        return Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            CustomerFactory::getCustomer()
        );
    }
}
