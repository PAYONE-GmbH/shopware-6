<?php

declare(strict_types=1);

namespace PayonePayment\Test\PaymentHandler;

use Faker\Factory;
use PayonePayment\PaymentMethod\PayonePaypal;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Processor;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;

class PaypalPaymentHandlerTest extends TestCase
{
    use KernelTestBehaviour;
    use FilesystemBehaviour;
    use CacheTestBehaviour;

    /** @var EntityRepositoryInterface */
    private $paymentMethodrepository;

    /** @var EntityRepositoryInterface */
    private $salesChannelRepository;

    /** @var EntityRepositoryInterface */
    private $customerRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    /** @var EntityRepositoryInterface */
    private $productRepository;

    /** @var EntityRepositoryInterface */
    private $manufacturerRepository;

    /** @var EntityRepositoryInterface */
    private $taxRepository;

    /** @var EntityRepositoryInterface */
    private $ruleRepository;

    /** @var SalesChannelContextFactory */
    private $contextFactory;

    /** @var CartService */
    private $cartService;

    /** @var Processor */
    private $cartProcessor;

    /** @var PaymentService */
    private $paymentService;

    /** @var Router */
    private $router;

    /** @var string */
    private $token;

    public function setUp(): void
    {
        $this->paymentMethodrepository = $this->getContainer()->get('payment_method.repository');
        $this->salesChannelRepository  = $this->getContainer()->get('sales_channel.repository');
        $this->customerRepository      = $this->getContainer()->get('customer.repository');
        $this->countryRepository       = $this->getContainer()->get('country.repository');
        $this->productRepository       = $this->getContainer()->get('product.repository');
        $this->manufacturerRepository  = $this->getContainer()->get('product_manufacturer.repository');
        $this->taxRepository           = $this->getContainer()->get('tax.repository');
        $this->ruleRepository          = $this->getContainer()->get('rule.repository');

        $this->contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->cartProcessor  = $this->getContainer()->get(Processor::class);
        $this->cartService    = $this->getContainer()->get(CartService::class);
        $this->paymentService = $this->getContainer()->get(PaymentService::class);
        $this->router         = $this->getContainer()->get('router');

        $this->token = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
        $this->router->getContext()->setHost('example.com');
    }

    public function testPaymentHandler(): void
    {
        $context = $this->createCheckoutContext((new PayonePaypal())->getId());
        $product = $this->getProduct();

        $lineItem = new LineItem($product->getId(), ProductCollector::LINE_ITEM_TYPE, 1);
        $lineItem->setPayload(['id' => $product->getId()]);

        $cart = $this->cartService->add($this->cartService->getCart($this->token, $context), $lineItem, $context);

        $processedCart = $this->cartProcessor->process($cart, $context, new CartBehavior());

        $order = $this->cartService->order($processedCart, $context);

        $response = $this->paymentService->handlePaymentByOrder($order, $context);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function getProduct(): ProductEntity
    {
        $faker = Factory::create();

        /** @var TaxEntity $tax */
        $tax = $this->taxRepository->search(new Criteria(), Context::createDefaultContext())->first();

        /** @var ProductManufacturerEntity $manufacturer */
        $manufacturer = $this->manufacturerRepository->search(new Criteria(), Context::createDefaultContext())->first();

        $product = [
            'price'           => ['gross' => 100, 'net' => 100 / 1.19, 'linked' => true],
            'name'            => $faker->name,
            'productNumber'   => 'Product-' . $faker->randomDigitNotNull,
            'description'     => $faker->text(),
            'descriptionLong' => $faker->text,
            'taxId'           => $tax->getId(),
            'manufacturerId'  => $manufacturer->getId(),
            'active'          => true,
            'stock'           => 200,
        ];

        $this->productRepository->upsert([$product], Context::createDefaultContext());

        /** @var ProductEntity $product */
        return $this->productRepository->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function createCheckoutContext(string $paymentMethod): SalesChannelContext
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository->search(new Criteria(), Context::createDefaultContext())->first();

        $customer = $this->createCustomer($paymentMethod, $salesChannel->getId());

        $context = $this->contextFactory->create($this->token, $salesChannel->getId(), [
            SalesChannelContextService::CUSTOMER_ID       => $customer->getId(),
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod,
        ]);

        $context->setRuleIds($this->fetchAllRules());

        return $context;
    }

    private function createCustomer(string $paymentMethod, string $salesChannel): CustomerEntity
    {
        $faker = Factory::create();

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('iso', 'DE'));

        /** @var CountryEntity $country */
        $country = $this->countryRepository->search($criteria, Context::createDefaultContext())->first();

        $customerData = [
            'salesChannelId'        => $salesChannel,
            'defaultBillingAddress' => [
                'firstName'    => $faker->firstName,
                'lastName'     => $faker->lastName,
                'street'       => $faker->streetAddress,
                'city'         => $faker->city,
                'zipcode'      => $faker->postcode,
                'salutationId' => $this->getValidSalutationId(),
                'countryId'    => $country->getId(),
            ],
            'defaultShippingAddress' => [
                'firstName'    => $faker->firstName,
                'lastName'     => $faker->lastName,
                'street'       => $faker->streetAddress,
                'city'         => $faker->city,
                'zipcode'      => $faker->postcode,
                'salutationId' => $this->getValidSalutationId(),
                'countryId'    => $country->getId(),
            ],
            'defaultPaymentMethodId' => $paymentMethod,
            'groupId'                => Defaults::FALLBACK_CUSTOMER_GROUP,
            'email'                  => $faker->email,
            'password'               => $faker->password,
            'firstName'              => $faker->firstName,
            'lastName'               => $faker->lastName,
            'salutationId'           => $this->getValidSalutationId(),
            'customerNumber'         => 'PAYONE-' . $faker->randomDigitNotNull,
            'birthday'               => $faker->date(),
        ];

        $this->customerRepository->create([$customerData], Context::createDefaultContext());

        /** @var CustomerEntity $customer */
        return $this->customerRepository->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function getValidSalutationId(): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->getContainer()->get('salutation.repository');

        $criteria = (new Criteria())->setLimit(1);

        return $repository->searchIds($criteria, Context::createDefaultContext())->getIds()[0];
    }

    /**
     * @return string[]
     */
    private function fetchAllRules(): array
    {
        /** @var RuleEntity[] $rules */
        $rules = $this->ruleRepository->search(new Criteria(), Context::createDefaultContext())->getIterator();

        $result = [];
        foreach ($rules as $rule) {
            $result[] = $rule->getId();
        }

        return $result;
    }
}
