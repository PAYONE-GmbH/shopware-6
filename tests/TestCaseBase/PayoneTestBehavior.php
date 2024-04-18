<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\Components\Helper\OrderFetcher;
use PayonePayment\Constants;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Cart\Calculator;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
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
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SessionTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait PayoneTestBehavior
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;
    use SessionTestBehaviour;

    /**
     * @deprecated use `createCartWithProduct`
     */
    protected function fillCart(SalesChannelContext $context, ?float $totalPrice = Constants::DEFAULT_PRODUCT_PRICE): Cart
    {
        $cart = static::getContainer()->get(CartService::class)->createNew($context->getToken());

        $productId = $this->getRandomProduct($context, 1, false, $totalPrice ? [
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => $totalPrice, 'net' => $totalPrice / 1.19, 'linked' => false]],
        ] : [])->getId();

        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));

        $cart->setPrice($this->createPrice($totalPrice));

        return $cart;
    }

    /**
     * creates a cart with calculated sums
     */
    protected function createCartWithProduct(SalesChannelContext $context, float $itemPrice = Constants::DEFAULT_PRODUCT_PRICE, int $qty = 1): Cart
    {
        $cartService = static::getContainer()->get(CartService::class);
        $cartItemCalculator = static::getContainer()->get(Calculator::class);

        $cart = $cartService->createNew($context->getToken());

        $productId = $this->getRandomProduct($context, $qty, false, [
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => $itemPrice, 'net' => $itemPrice / 1.19, 'linked' => false]],
        ])->getId();

        $lineItem = (new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, $qty))
            ->setPriceDefinition(new QuantityPriceDefinition($itemPrice, new TaxRuleCollection([]), $qty))
            ->setLabel('lineItem1');

        $cart->add($lineItem);

        $cart->setPrice($this->createPrice($itemPrice * $qty));
        $lineItems = $cartItemCalculator->calculate($cart->getLineItems(), $context, new CartBehavior());
        $cart->setLineItems($lineItems);

        return $cart;
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

        $orderFetcher = static::getContainer()->get(OrderFetcher::class);
        $orderId = $this->placeRandomOrder($salesChannelContext);

        return $orderFetcher->getOrderById($orderId, $salesChannelContext->getContext());
    }

    protected function getRatepayProfileStruct(
        string $paymentHandler,
        string $requestAction = AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
    ): RatepayProfileStruct {
        return new RatepayProfileStruct(
            '88880103',
            'EUR',
            TestDefaults::SALES_CHANNEL,
            $paymentHandler,
            $requestAction
        );
    }

    protected function getFinancialTransactionStruct(ParameterBag $dataBag, string $paymentHandler, string $request): FinancialTransactionStruct
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order = $this->getRandomOrder($salesChannelContext);

        return new FinancialTransactionStruct(
            $this->getPaymentTransaction($order, $paymentHandler),
            $salesChannelContext->getContext(),
            $dataBag,
            $paymentHandler,
            $request
        );
    }

    protected function getPaymentTransactionStruct(
        RequestDataBag $dataBag,
        string $paymentHandler,
        string $requestAction
    ): PaymentTransactionStruct {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $order = $this->getRandomOrder($salesChannelContext);

        return new PaymentTransactionStruct(
            $this->getPaymentTransaction($order, $paymentHandler),
            $dataBag,
            $salesChannelContext,
            $paymentHandler,
            $requestAction
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

        $payoneTransactionData = new PayonePaymentOrderTransactionDataEntity();
        $payoneTransactionData->assign([
            'id' => Uuid::randomHex(),
            'transactionId' => Constants::PAYONE_TRANSACTION_ID,
            'sequenceNumber' => 0,
            'lastRequest' => 'authorization',
            'authorizationType' => 'authorization',
        ]);

        $orderTransactionEntity->addExtension(
            PayonePaymentOrderTransactionExtension::NAME,
            $payoneTransactionData
        );

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName('');
        $orderTransactionEntity->setStateMachineState($stateMachineState);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    protected function getOrderLineItem(float $amount): OrderLineItemCollection
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

    protected function getRequestStack(RequestDataBag $dataBag, array $attributes = []): RequestStack
    {
        $stack = new RequestStack();

        $request = new Request([], $dataBag->all(), $attributes);
        $stack->push($request);

        return $stack;
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return static::getContainer()->get(CheckoutConfirmPageLoader::class);
    }

    protected function getRequestWithSession(array $sessionVariables): Request
    {
        $session = $this->getSession();
        $session->clear(); // make sure that session is empty

        foreach ($sessionVariables as $key => $value) {
            $session->set($key, $value);
        }

        $request = new Request();
        $request->setSession($session);

        return $request;
    }
}
