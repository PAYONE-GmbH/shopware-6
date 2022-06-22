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
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\TestDefaults;

class RatepayControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRatepayInstallmentCalculationRequestByRate(): void
    {
        $this->markTestSkipped('This test uses the real client, we need to further discuss if this is ok.');

        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);

        /** @var RatepayController $controller */
        $controller = $this->getContainer()->get(RatepayController::class);
        $this->fillCart($salesChannelContext->getToken());

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => 'rate',
            'ratepayInstallmentValue' => 10,
        ]);

        $controller->calculation($dataBag, $salesChannelContext);
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
