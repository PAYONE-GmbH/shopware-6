<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\Components\Helper\OrderFetcher;
use PayonePayment\Constants;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
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
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait PayoneTestBehavior
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
                    'salesChannelId' => Defaults::SALES_CHANNEL,
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

    protected function getFinancialTransactionStruct(ParameterBag $dataBag, string $paymentHandler, string $request): FinancialTransactionStruct
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order               = $this->getRandomOrder($salesChannelContext);

        return new FinancialTransactionStruct(
            $this->getPaymentTransaction($order, $paymentHandler),
            $salesChannelContext->getContext(),
            $dataBag,
            $paymentHandler,
            $request
        );
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
            CustomFieldInstaller::TRANSACTION_ID     => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER    => 0,
            CustomFieldInstaller::LAST_REQUEST       => 'authorization',
            CustomFieldInstaller::AUTHORIZATION_TYPE => 'authorization',
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName('');
        $orderTransactionEntity->setStateMachineState($stateMachineState);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
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

    protected function getRequestStack(RequestDataBag $dataBag): RequestStack
    {
        $stack = new RequestStack();

        $request = new Request([], $dataBag->all());
        $stack->push($request);

        return $stack;
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return $this->getContainer()->get(CheckoutConfirmPageLoader::class);
    }
}
