<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use PayonePayment\Service\CurrencyPrecisionService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class LineItemHydrator implements LineItemHydratorInterface
{
    /**
     * @see https://docs.payone.com/display/public/PLATFORM/it%5Bn%5D+-+definition
     */
    final public const TYPE_GOODS = 'goods';

    final public const TYPE_VOUCHER = 'voucher';

    final public const TYPE_SHIPMENT = 'shipment';

    final public const TYPE_HANDLING = 'handling';

    final public const PAYONE_ARRAY_KEY_TYPE = 'it';

    final public const PAYONE_ARRAY_KEY_NUMBER = 'id';

    final public const PAYONE_ARRAY_KEY_PRICE = 'pr';

    final public const PAYONE_ARRAY_KEY_QTY = 'no';

    final public const PAYONE_ARRAY_KEY_NAME = 'de';

    final public const PAYONE_ARRAY_KEY_TAX_RATE = 'va';

    public function __construct(
        private readonly CurrencyPrecisionService $currencyPrecision,
        private readonly EntityRepository $shipmentRepository,
    ) {
    }

    #[\Override]
    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderEntity $order,
        array $requestLines,
        bool $includeShippingCosts,
    ): array {
        $orderLineItems = $order->getLineItems();

        if (null === $orderLineItems) {
            return [];
        }

        $requestLineItems = [];

        foreach ($requestLines as $orderLine) {
            if (!\array_key_exists('id', $orderLine)) {
                continue;
            }

            $lineItem = $orderLineItems->get($orderLine['id']);

            if (null === $lineItem) {
                continue;
            }

            if ($this->isCustomizedProduct($lineItem)) {
                continue;
            }

            if (empty($orderLine['quantity'])) {
                continue;
            }

            $requestLineItems[] = $this->getLineItemRequest(
                $lineItem,
                $orderLine['quantity'],
            );
        }

        if ($includeShippingCosts && $deliveries = $order->getDeliveries()) {
            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $order->getLanguageId()),
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $currency);
    }

    #[\Override]
    public function mapCartLines(Cart $cart, SalesChannelContext $salesChannelContext): array
    {
        $context          = $salesChannelContext->getContext();
        $requestLineItems = [];

        foreach ($cart->getLineItems() as $lineItem) {
            $requestLineItems[] = $this->getLineItemRequest(
                $lineItem,
                $lineItem->getQuantity(),
            );
        }

        $deliveries = $cart->getDeliveries();

        if (0 < $deliveries->count()) {
            $requestLineItems = \array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $context->getLanguageId(), $context),
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $salesChannelContext->getCurrency());
    }

    #[\Override]
    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, Context $context): array
    {
        $lineItemCollection = $order->getLineItems();
        $requestLineItems   = [];

        if (null === $lineItemCollection) {
            return [];
        }

        foreach ($lineItemCollection as $lineItem) {
            if ($this->isCustomizedProduct($lineItem)) {
                continue;
            }

            $requestLineItems[] = $this->getLineItemRequest(
                $lineItem,
                $lineItem->getQuantity(),
            );
        }

        if ($deliveries = $order->getDeliveries()) {
            $requestLineItems = \array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $order->getLanguageId()),
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $currency);
    }

    protected function mapItemType(string|null $itemType): string
    {
        if (LineItem::CREDIT_LINE_ITEM_TYPE === $itemType) {
            return self::TYPE_VOUCHER;
        }

        if (PromotionProcessor::LINE_ITEM_TYPE === $itemType) {
            return self::TYPE_VOUCHER;
        }

        return self::TYPE_GOODS;
    }

    private function isCustomizedProduct(OrderLineItemEntity $lineItemEntity): bool
    {
        try {
            $lineItemType = $lineItemEntity->getType();
            if (
                \class_exists(CustomizedProductsCartDataCollector::class)
                && CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItemType
                && null === $lineItemEntity->getParentId()
            ) {
                return true;
            }
        } catch (\Exception) {
            // Catch class not found if SwagCustomizedProducts plugin is not installed
        }

        return false;
    }

    private function getLineItemRequest(
        LineItem|OrderLineItemEntity $lineItemEntity,
        int $quantity,
    ): array {
        $productNumber = \is_array($lineItemEntity->getPayload()) && \array_key_exists('productNumber', $lineItemEntity->getPayload())
            ? $lineItemEntity->getPayload()['productNumber']
            : null
        ;

        if (!$productNumber) {
            if ($lineItemEntity instanceof LineItem) {
                $productNumber = $lineItemEntity->getId();
            } elseif ($lineItemEntity instanceof OrderLineItemEntity) {
                $productNumber = $lineItemEntity->getIdentifier();
            }
        }

        $taxes = $lineItemEntity->getPrice()?->getCalculatedTaxes();

        $taxRate = null === $taxes || null === $taxes->first()
            ? 0.0
            : $taxes->first()->getTaxRate()
        ;

        $unitPrice = null;
        if ($lineItemEntity instanceof LineItem) {
            $unitPrice = $lineItemEntity->getPrice()?->getUnitPrice();
        } elseif ($lineItemEntity instanceof OrderLineItemEntity) {
            $unitPrice = $lineItemEntity->getUnitPrice();
        }

        return $this->getRequestItem(
            $this->mapItemType($lineItemEntity->getType()),
            $productNumber,
            $lineItemEntity->getLabel() ?? '',
            $unitPrice ?? 0.0,
            $quantity,
            $taxRate,
        );
    }

    private function getShippingItems(
        DeliveryCollection|OrderDeliveryCollection $deliveryCollection,
        string $languageId,
        Context|null $context = null,
    ): array {
        if (null === $context) {
            $context = Context::createCLIContext();
        }

        /** @var Delivery|OrderDeliveryEntity|null $deliveryEntity */
        $deliveryEntity = $deliveryCollection->first();

        if (null === $deliveryEntity) {
            return [];
        }

        $shippingCosts = $deliveryEntity->getShippingCosts();

        if ($shippingCosts->getTotalPrice() <= 0 || 0 === $shippingCosts->getCalculatedTaxes()->count()) {
            return [];
        }

        $languages = $context->getLanguageIdChain();

        if (!\in_array($languageId, $languages, true)) {
            \array_splice($languages, 0, 0, $languageId);

            $context->assign([ 'languageIdChain' => $languages ]);
        }

        $shippingMethod = null;
        if ($deliveryEntity instanceof OrderDeliveryEntity) {
            $shippingMethod = $this->shipmentRepository->search(
                new Criteria([ $deliveryEntity->getShippingMethodId() ]),
                $context,
            )->first();
        } elseif ($deliveryEntity instanceof Delivery) {
            $shippingMethod = $deliveryEntity->getShippingMethod();
        }

        /** @var ShippingMethodEntity|null $shippingMethod */
        if (null === $shippingMethod) {
            return [];
        }

        $items = [];

        foreach ($shippingCosts->getCalculatedTaxes() as $shipmentPosition) {
            /** @var CalculatedTax $shipmentPosition */
            $items[] = $this->getRequestItem(
                self::TYPE_SHIPMENT,
                '', // got be filled by `addShippingItemsToItemList`
                $shippingMethod->getTranslation('name') ?? '',
                $shipmentPosition->getPrice(),
                1,
                $shipmentPosition->getTaxRate(),
            );
        }

        return $items;
    }

    /**
     * converts the item-list (based on `addRequestItem`) to the payone item list parameter
     */
    private function convertItemListToPayoneArray(array $items, CurrencyEntity $currency): array
    {
        $payoneList = [];

        foreach ($items as $index => $item) {
            ++$index; // index needs to be greater than 0.

            if (self::TYPE_SHIPMENT === $item['it'] && empty($item['id'])) {
                // add product number for shipping (just the index of the item in the list)
                $item['id'] = $index;
            }

            // round/format price and tax amount
            $item['pr'] = $this->currencyPrecision->getRoundedItemAmount($item['pr'], $currency);
            $item['va'] = $this->currencyPrecision->getRoundedItemAmount($item['va'], $currency);

            foreach ($item as $key => $value) {
                $payoneList[sprintf('%s[%d]', $key, $index)] = $value;
            }
        }

        return $payoneList;
    }

    private function getRequestItem(
        string $itemType,
        string $itemNumber,
        string $itemName,
        float $itemPrice,
        int $itemQty,
        float $itemTaxRate,
    ): array {
        return [
            self::PAYONE_ARRAY_KEY_TYPE     => $itemType,
            self::PAYONE_ARRAY_KEY_NUMBER   => $itemNumber,
            self::PAYONE_ARRAY_KEY_PRICE    => $itemPrice,
            self::PAYONE_ARRAY_KEY_QTY      => $itemQty,
            self::PAYONE_ARRAY_KEY_NAME     => $itemName,
            self::PAYONE_ARRAY_KEY_TAX_RATE => $itemTaxRate,
        ];
    }
}
