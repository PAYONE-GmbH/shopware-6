<?php

declare(strict_types=1);

namespace PayonePayment\Test\TestCaseBase;

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
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;

trait CheckoutTestBehavior
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    protected function fillCart(string $contextToken, float $totalPrice): Cart
    {
        $cart = $this->getContainer()->get(CartService::class)->createNew($contextToken);

        $productId = $this->createProduct($totalPrice);
        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));

        $cart->setPrice($this->createPrice($totalPrice));

        return $cart;
    }

    protected function createProduct(float $price): string
    {
        $productId = Uuid::randomHex();

        $product = [
            'id'            => $productId,
            'name'          => 'Test product',
            'productNumber' => '123456789',
            'stock'         => 1,
            'price'         => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => $price, 'net' => $price, 'linked' => false],
            ],
            'manufacturer' => ['id' => $productId, 'name' => 'shopware AG'],
            'tax'          => ['id' => $productId, 'name' => 'testTaxRate', 'taxRate' => 0],
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

    protected function createPrice(float $price): CartPrice
    {
        return new CartPrice(
            $price,
            $price,
            $price,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return $this->getContainer()->get(CheckoutConfirmPageLoader::class);
    }
}
