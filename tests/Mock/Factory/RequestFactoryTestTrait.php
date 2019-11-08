<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\System\Country\CountryEntity;
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

    private function getCustomerRequest(): CustomerRequest
    {
        $languageRepository = $this->createMock(EntityRepository::class);
        $languageEntity     = new LanguageEntity();
        $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
        $localeEntity = new LocaleEntity();
        $localeEntity->setCode('de_DE');
        $languageEntity->setLocale($localeEntity);
        $languageRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$languageEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $salutationRepository = $this->createMock(EntityRepository::class);
        $salutationEntity     = new SalutationEntity();
        $salutationEntity->setId(Constants::SALUTATION_ID);
        $salutationRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$salutationEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $countryRepository = $this->createMock(EntityRepository::class);
        $countryEntity     = new CountryEntity();
        $countryEntity->setId(Constants::COUNTRY_ID);
        $countryRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$countryEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        return new CustomerRequest($languageRepository, $salutationRepository, $countryRepository, $requestStack);
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
