<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Ratepay;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;
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

class RatepayInstallmentCalculationRequestFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRatepayInstallmentCalculationParametersByRate(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $factory             = $this->getContainer()->get(RequestParameterFactory::class);
        $cart                = $this->fillCart($salesChannelContext->getToken());

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => 'rate',
            'ratepayInstallmentValue' => 10,
        ]);

        $request = $factory->getRequestParameter(
            new RatepayCalculationStruct(
                $cart,
                $dataBag,
                $salesChannelContext,
                PayoneRatepayInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
            )
        );

        Assert::assertArraySubset(
            [
                'request'                                    => 'genericpayment',
                'clearingtype'                               => 'fnc',
                'financingtype'                              => 'RPS',
                'add_paydata[action]'                        => 'calculation',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[calculation_type]'              => 'calculation-by-rate',
                'add_paydata[rate]'                          => 10,
                'amount'                                     => 1599,
                'currency'                                   => 'EUR',
            ],
            $request
        );
    }

    public function testRatepayInstallmentCalculationParametersByTime(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $factory             = $this->getContainer()->get(RequestParameterFactory::class);
        $cart                = $this->fillCart($salesChannelContext->getToken());

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => 'time',
            'ratepayInstallmentValue' => 10,
        ]);

        $request = $factory->getRequestParameter(
            new RatepayCalculationStruct(
                $cart,
                $dataBag,
                $salesChannelContext,
                PayoneRatepayInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
            )
        );

        Assert::assertArraySubset(
            [
                'request'                                    => 'genericpayment',
                'clearingtype'                               => 'fnc',
                'financingtype'                              => 'RPS',
                'add_paydata[action]'                        => 'calculation',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[calculation_type]'              => 'calculation-by-time',
                'add_paydata[month]'                         => 10,
                'amount'                                     => 1599,
                'currency'                                   => 'EUR',
            ],
            $request
        );
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
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15.99, 'net' => 10, 'linked' => false],
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
            10,
            15.99,
            15.99,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );
    }
}
