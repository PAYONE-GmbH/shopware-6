<?php

declare(strict_types=1);

namespace PayonePayment\Test\Controller;

use PayonePayment\Storefront\Controller\Ratepay\RatepayController;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Symfony\Component\HttpFoundation\Request;

class RatepayControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testRatepayInstallmentCalculationRequestByRate(): void
    {
        $this->markTestSkipped('This test uses the real client, we need to further discuss if this is ok.');

        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        /** @var RatepayController $controller */
        $controller = $this->getContainer()->get(RatepayController::class);
        $this->fillCart($context->getToken());

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => 'rate',
            'ratepayInstallmentValue' => 10,
        ]);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'shopware.test');
        $this->getContainer()->get('request_stack')->push($request);

        $response = $controller->calculation($dataBag, $context);

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return $this->getContainer()->get(CheckoutConfirmPageLoader::class);
    }

    private function fillCart(string $contextToken): Cart
    {
        $cart = $this->getContainer()->get(CartService::class)->createNew($contextToken);

        $productId = $this->createProduct();
        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));

        $cart->setPrice($this->createPrice());

        return $cart;
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();

        $product = [
            'id'            => $productId,
            'name'          => 'Test product',
            'productNumber' => '123456789',
            'stock'         => 1,
            'price'         => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 159.90, 'net' => 100, 'linked' => false],
            ],
            'manufacturer' => ['id' => $productId, 'name' => 'shopware AG'],
            'tax'          => ['id' => $productId, 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories'   => [
                ['id' => $productId, 'name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'id'             => $productId,
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility'     => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $this->getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        return $productId;
    }

    private function createPrice(): CartPrice
    {
        return new CartPrice(
            100,
            159.90,
            159.90,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );
    }
}
