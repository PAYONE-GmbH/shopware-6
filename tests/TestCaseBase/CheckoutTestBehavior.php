<?php

declare(strict_types=1);

namespace PayonePayment\Test\TestCaseBase;

use PayonePayment\Components\Helper\OrderFetcher;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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

    protected function getRandomOrder(?SalesChannelContext $salesChannelContext = null): ?OrderEntity
    {
        if ($salesChannelContext === null) {
            $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        }

        $orderFetcher = $this->getContainer()->get(OrderFetcher::class);
        $orderId      = $this->placeRandomOrder($salesChannelContext);

        return $orderFetcher->getOrderById($orderId, $salesChannelContext->getContext());
    }

    protected function getPaymentTransactionStruct(RequestDataBag $dataBag, string $paymentHandler): PaymentTransactionStruct
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);

        return new PaymentTransactionStruct(
            $this->getPaymentTransaction($order, $paymentHandler),
            $dataBag,
            $salesChannelContext,
            $paymentHandler,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );
    }

    protected function getPaymentTransaction(OrderEntity $orderEntity, string $paymentHandler): PaymentTransaction
    {
        $orderEntity->setTransactions(new OrderTransactionCollection([]));

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier($paymentHandler);

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);
        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return $this->getContainer()->get(CheckoutConfirmPageLoader::class);
    }
}
