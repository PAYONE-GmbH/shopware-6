<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Factory;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Builder\Capture\CaptureRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\CreditCard\PreAuthorizeRequestParameterBuilder as CreditCardPreAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\CustomerRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Debit\AuthorizeRequestParameterBuilder as DebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\GeneralTransactionRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\OrderLinesRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Paypal\AuthorizeRequestParameterBuilder as PaypalAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Refund\RefundRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\ReturnUrlRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\ShippingInformationRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\SofortBanking\AuthorizeRequestParameterBuilder as SofortBankingAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\SystemRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
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

trait RequestParameterFactoryTestTrait
{
    protected function getRequestParameterFactory(SalesChannelContext $salesChannelContext): RequestParameterFactory
    {
        return new RequestParameterFactory(
            [
                new ShippingInformationRequestParameterBuilder(),
                new SofortBankingAuthorizeRequestParameterBuilder(),
                new PaypalAuthorizeRequestParameterBuilder(),
                new CreditCardPreAuthorizeRequestParameterBuilder(),
                new DebitAuthorizeRequestParameterBuilder(),
                new CaptureRequestParameterBuilder(new CurrencyPrecision()),
                new RefundRequestParameterBuilder(new CurrencyPrecision()),
                new OrderLinesRequestParameterBuilder(new LineItemHydrator(new CurrencyPrecision())),
                $this->getSystemRequestBuilder(),
                $this->getGeneralTransactionRequestBuilder($salesChannelContext),
                $this->getCustomerRequestBuilder(),
                $this->getReturnUrlRequestBuilder(),
            ]
        );
    }

    protected function getSystemRequestBuilder(): SystemRequestParameterBuilder
    {
        $pluginService = $this->createMock(PluginService::class);
        $pluginEntity  = new PluginEntity();
        $pluginEntity->setVersion('1');
        $pluginService->method('getPluginByName')->willReturn($pluginEntity);

        return new SystemRequestParameterBuilder($pluginService, '1.0.0-test', new ConfigReaderMock([]));
    }

    protected function getReturnUrlRequestBuilder(): ReturnUrlRequestParameterBuilder
    {
        return new ReturnUrlRequestParameterBuilder($this->createMock(RedirectHandler::class));
    }

    protected function getGeneralTransactionRequestBuilder(SalesChannelContext $salesChannelContext): GeneralTransactionRequestParameterBuilder
    {
        $currencyRepositoryMock = $this->createMock(EntityRepositoryInterface::class);

        $builder = new GeneralTransactionRequestParameterBuilder(new CartHasher(new CurrencyPrecision()), new ConfigReaderMock([]), $currencyRepositoryMock, new CurrencyPrecision());

        $currencyRepositoryMock->method('search')->willReturn(
            $this->getCurrencySearchResult($salesChannelContext->getContext())
        );

        return $builder;
    }

    protected function getCurrencyEntity(): CurrencyEntity
    {
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode(Constants::CURRENCY_ISO);

        if (method_exists($currencyEntity, 'setItemRounding')) {
            $currencyEntity->setItemRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, false));
            $currencyEntity->setTotalRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, false));
        } else {
            /** @phpstan-ignore-next-line */
            $currencyEntity->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        }

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

    protected function getPaymentTransaction(string $handlerIdentifier): PaymentTransaction
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setOrderNumber('1');
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setTransactions(new OrderTransactionCollection([]));
        $orderEntity->setCurrency($this->getCurrencyEntity());

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier($handlerIdentifier);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $paymentTransactionStruct = new AsyncPaymentTransactionStruct($orderTransactionEntity, $orderEntity, 'test-url');

        return PaymentTransaction::fromAsyncPaymentTransactionStruct($paymentTransactionStruct, $orderEntity);
    }

    protected function getLineItem(int $amount): OrderLineItemCollection
    {
        $lineItemTaxRules = new TaxRule(Constants::CURRENCY_TAX_RATE);

        $taxRuleCollection = new TaxRuleCollection();
        $taxRuleCollection->add($lineItemTaxRules);

        $lineItemtax = new CalculatedTax(
            Constants::LINE_ITEM_UNIT_PRICE + (Constants::LINE_ITEM_UNIT_PRICE / 100 * Constants::CURRENCY_TAX_RATE),
            Constants::CURRENCY_TAX_RATE,
            Constants::LINE_ITEM_UNIT_PRICE
        );

        $calculatedTaxCollection = new CalculatedTaxCollection();
        $calculatedTaxCollection->add($lineItemtax);

        $lineItemPrice = new CalculatedPrice(
            Constants::LINE_ITEM_UNIT_PRICE,
            Constants::LINE_ITEM_UNIT_PRICE * Constants::LINE_ITEM_QUANTITY,
            $calculatedTaxCollection,
            $taxRuleCollection,
            Constants::LINE_ITEM_QUANTITY
        );

        $lineItemCollection = new OrderLineItemCollection();

        for ($i = 0; $i < $amount; ++$i) {
            $lineItem = new OrderLineItemEntity();
            $lineItem->setId(Constants::LINE_ITEM_ID . $i);
            $lineItem->setType(Constants::LINE_ITEM_TYPE);
            $lineItem->setIdentifier(Constants::LINE_ITEM_IDENTIFIER);
            $lineItem->setUnitPrice(Constants::LINE_ITEM_UNIT_PRICE);
            $lineItem->setPrice($lineItemPrice);
            $lineItem->setLabel(Constants::LINE_ITEM_LABEL);
            $lineItem->setQuantity(Constants::LINE_ITEM_QUANTITY);

            $lineItemCollection->add($lineItem);
        }

        return $lineItemCollection;
    }

    /** @phpstan-ignore-next-line */
    private function getLanguageRepository()
    {
        $languageRepository = $this->createMock(EntityRepositoryInterface::class);
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

        return $languageRepository;
    }

    /** @phpstan-ignore-next-line */
    private function getSalutationRepository()
    {
        $salutationRepository = $this->createMock(EntityRepositoryInterface::class);
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

        return $salutationRepository;
    }

    /** @phpstan-ignore-next-line */
    private function getCountryRepository()
    {
        $countryRepository = $this->createMock(EntityRepositoryInterface::class);
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

        return $countryRepository;
    }

    private function getCustomerRequestBuilder(): CustomerRequestParameterBuilder
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        return new CustomerRequestParameterBuilder(
            $this->getLanguageRepository(),
            $this->getSalutationRepository(),
            $this->getCountryRepository(),
            $requestStack
        );
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
