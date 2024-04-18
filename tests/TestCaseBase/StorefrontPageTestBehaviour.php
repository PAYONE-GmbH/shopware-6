<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\Constants;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Copied from https://github.com/shopware/shopware/blob/trunk/tests/integration/Storefront/Page/StorefrontPageTestBehaviour.php
 */
trait StorefrontPageTestBehaviour
{
    use TaxAddToSalesChannelTestBehaviour;

    /**
     * @param class-string<object> $expectedClass
     */
    public static function assertPageEvent(
        string $expectedClass,
        PageLoadedEvent $event,
        SalesChannelContext $salesChannelContext,
        Request $request,
        Struct $page
    ): void {
        TestCase::assertInstanceOf($expectedClass, $event);
        TestCase::assertSame($salesChannelContext, $event->getSalesChannelContext());
        TestCase::assertSame($salesChannelContext->getContext(), $event->getContext());
        TestCase::assertSame($request, $event->getRequest());
        TestCase::assertSame($page, $event->getPage());
    }

    /**
     * @param class-string<object> $expectedClass
     */
    public static function assertPageletEvent(
        string $expectedClass,
        PageletLoadedEvent $event,
        SalesChannelContext $salesChannelContext,
        Request $request,
        Struct $page
    ): void {
        TestCase::assertInstanceOf($expectedClass, $event);
        TestCase::assertSame($salesChannelContext, $event->getSalesChannelContext());
        TestCase::assertSame($salesChannelContext->getContext(), $event->getContext());
        TestCase::assertSame($request, $event->getRequest());
        TestCase::assertSame($page, $event->getPagelet());
    }

    abstract protected function getPageLoader();

    protected function expectParamMissingException(string $paramName): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "' . $paramName . '" is missing');
    }

    protected function placeRandomOrder(SalesChannelContext $context): string
    {
        $product = $this->getRandomProduct($context);

        $lineItem = (new LineItem($product->getId(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId()))
            ->setRemovable(true)
            ->setStackable(true);

        $cartService = $this->getContainer()->get(CartService::class);
        $cart = $cartService->getCart($context->getToken(), $context);
        $cart->add($lineItem);

        return $cartService->order($cart, $context, new RequestDataBag());
    }

    /**
     * @param array<int|string, mixed> $config
     */
    protected function getRandomProduct(SalesChannelContext $context, ?int $stock = 1, ?bool $isCloseout = false, array $config = []): ProductEntity
    {
        $productNumber = 'phpunit-' . md5(json_encode([$stock, $isCloseout, $config]));

        $productRepository = $this->getContainer()->get('product.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $criteria->setLimit(1);
        $product = $productRepository->search($criteria, $context->getContext())->first();

        if ($product instanceof ProductEntity) {
            return $product;
        }

        $id = Uuid::randomHex();
        $itemPrice = Constants::DEFAULT_PRODUCT_PRICE;
        $data = [
            'id' => $id,
            'productNumber' => $productNumber,
            'stock' => $stock,
            'name' => StorefrontPageTestConstants::PRODUCT_NAME,
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => $itemPrice, 'net' => $itemPrice / 1.19, 'linked' => false]],
            'tax' => ['id' => '2abf31f1c4d94effb0e42c9bdcab0dd0', 'name' => 'test', 'taxRate' => 19],
            'active' => true,
            'isCloseout' => $isCloseout,
            'visibilities' => [
                ['salesChannelId' => $context->getSalesChannel()->getId(), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $data = array_merge_recursive($data, $config);

        $productRepository->create([$data], $context->getContext());
        $this->addTaxDataToSalesChannel($context, $data['tax']);

        /** @var SalesChannelRepository $storefrontProductRepository */
        $storefrontProductRepository = $this->getContainer()->get('sales_channel.product.repository');
        $searchResult = $storefrontProductRepository->search(new Criteria([$id]), $context);

        /** @var ProductEntity $product */
        $product = $searchResult->first();

        return $product;
    }

    protected function createSalesChannelContextWithNavigation(): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'id' => 'd70c06716f8848b685f8faa7ff1bbeae',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        return $this->createContext($data, []);
    }

    protected function createSalesChannelContextWithLoggedInCustomerAndWithNavigation(): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'id' => '5339bf0dc99f4579a69119a021453085',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        return $this->createContext($data, [
            SalesChannelContextService::CUSTOMER_ID => $this->createCustomer()->getId(),
        ]);
    }

    protected function createSalesChannelContext(): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'id' => '626f01c28ba640bfb49c42ff41762e57',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        return $this->createContext($data, []);
    }

    protected function catchEvent(string $eventName, ?object &$eventResult): void
    {
        $this->addEventListener($this->getContainer()->get('event_dispatcher'), $eventName, static function ($event) use (&$eventResult): void {
            $eventResult = $event;
        });
    }

    abstract protected static function getContainer(): ContainerInterface;

    private function createCustomer(): CustomerEntity
    {
        $context = Context::createDefaultContext();
        $addressId = '43419bb9d20d436ab096a902c5452544';

        /** @var EntityRepository $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');

        $criteria = new Criteria([Constants::CUSTOMER_ID]);
        $criteria->addAssociation('defaultBillingAddress');
        $customer = $customerRepo->search($criteria, $context)->first();

        if ($customer instanceof CustomerEntity) {
            // make sure that phoneNumber is not set (which may have been happened through other tests.
            if ($customer->getDefaultBillingAddress()->getPhoneNumber()) {
                /** @var EntityRepository $addressRepo */
                $addressRepo = $this->getContainer()->get('customer_address.repository');
                $addressRepo->upsert([['id' => $customer->getDefaultBillingAddressId(), 'phoneNumber' => null]], $context);
                $customer->getDefaultBillingAddress()->assign(['phoneNumber' => null]);
            }

            // make sure that birthday is not set (which may have been happened through other tests.
            if ($customer->getBirthday()) {
                $customerRepo->upsert([['id' => $customer->getId(), 'birthday' => null]], $context);
                $customer->assign(['birthday' => null]);
            }

            return $customer;
        }

        $data = [
            [
                'id' => Constants::CUSTOMER_ID,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'defaultShippingAddress' => [
                    'id' => $addressId,
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Musterstraße 1',
                    'city' => 'Schöppingen',
                    'zipcode' => '12345',
                    'salutationId' => $this->getValidSalutationId(),
                    'country' => ['id' => $this->getValidCountryId()],
                ],
                'defaultBillingAddressId' => $addressId,
                'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
                'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
                'email' => 'foo@bar.de',
                'password' => 'password',
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'salutationId' => $this->getValidSalutationId(),
                'customerNumber' => '12345',
            ],
        ];

        $customerRepo->create($data, $context);

        /** @var CustomerEntity $customer */
        $customer = $customerRepo->search(new Criteria([Constants::CUSTOMER_ID]), Context::createDefaultContext())->first();

        return $customer;
    }

    /**
     * @param array<string, mixed> $salesChannel
     * @param array<string, mixed> $options
     */
    private function createContext(array $salesChannel, array $options): SalesChannelContext
    {
        $factory = $this->getContainer()->get(SalesChannelContextFactory::class);
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $salesChannelEntity = null;
        if (isset($salesChannel['id'])) {
            $salesChannelEntity = $salesChannelRepository->search(new Criteria([$salesChannel['id']]), Context::createDefaultContext())->first();
        }

        $salesChannelId = $salesChannel['id'] ?? Uuid::randomHex();

        if (!$salesChannelEntity instanceof SalesChannelEntity) {
            $salesChannel['id'] = $salesChannelId;
            $salesChannel['customerGroupId'] = TestDefaults::FALLBACK_CUSTOMER_GROUP;

            $salesChannelRepository->create([$salesChannel], Context::createDefaultContext());
        }

        $context = $factory->create(Uuid::randomHex(), $salesChannelId, $options);

        $ruleLoader = $this->getContainer()->get(CartRuleLoader::class);
        $ruleLoader->loadByToken($context, $context->getToken());

        return $context;
    }
}
