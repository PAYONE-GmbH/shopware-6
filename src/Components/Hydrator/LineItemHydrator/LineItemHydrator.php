<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Exception;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class LineItemHydrator implements LineItemHydratorInterface
{
    /** @see https://docs.payone.com/display/public/PLATFORM/it%5Bn%5D+-+definition */
    public const TYPE_GOODS    = 'goods';
    public const TYPE_VOUCHER  = 'voucher';
    public const TYPE_SHIPMENT = 'shipment';
    public const TYPE_HANDLING = 'handling';

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    /** @var EntityRepositoryInterface */
    private $shipmentRepository;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision, EntityRepositoryInterface $shipmentRepository)
    {
        $this->currencyPrecision  = $currencyPrecision;
        $this->shipmentRepository = $shipmentRepository;
    }

    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderEntity $order,
        array $requestLines,
        bool $includeShippingCosts
    ): array {
        $orderLineItems = $order->getLineItems();

        if ($orderLineItems === null) {
            return [];
        }

        $requestLineItems = [];

        foreach ($requestLines as $orderLine) {
            if (!array_key_exists('id', $orderLine)) {
                continue;
            }

            $lineItem = $orderLineItems->get($orderLine['id']);

            if ($lineItem === null) {
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
                $orderLine['quantity']
            );
        }

        if ($includeShippingCosts && $deliveries = $order->getDeliveries()) {
            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $order->getLanguageId())
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $currency);
    }

    public function mapCartLines(Cart $cart, SalesChannelContext $salesChannelContext): array
    {
        $requestLineItems = [];

        foreach ($cart->getLineItems() as $lineItem) {
            $requestLineItems[] = $this->getLineItemRequest(
                $lineItem,
                $lineItem->getQuantity()
            );
        }

        if ($deliveries = $cart->getDeliveries()) {
            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $salesChannelContext->getLanguageId(), $salesChannelContext->getContext())
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $salesChannelContext->getCurrency());
    }

    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, Context $context): array
    {
        $lineItemCollection = $order->getLineItems();
        $requestLineItems   = [];

        if ($lineItemCollection === null) {
            return [];
        }

        foreach ($lineItemCollection as $lineItem) {
            if ($this->isCustomizedProduct($lineItem)) {
                continue;
            }

            $requestLineItems[] = $this->getLineItemRequest(
                $lineItem,
                $lineItem->getQuantity()
            );
        }

        if ($deliveries = $order->getDeliveries()) {
            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getShippingItems($deliveries, $order->getLanguageId())
            );
        }

        return $this->convertItemListToPayoneArray($requestLineItems, $order->getCurrency());
    }

    protected function mapItemType(?string $itemType): string
    {
        if ($itemType === LineItem::CREDIT_LINE_ITEM_TYPE) {
            return self::TYPE_VOUCHER;
        }

        if ($itemType === PromotionProcessor::LINE_ITEM_TYPE) {
            return self::TYPE_VOUCHER;
        }

        return self::TYPE_GOODS;
    }

    private function isCustomizedProduct(OrderLineItemEntity $lineItemEntity): bool
    {
        try {
            if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector') &&
                CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItemEntity->getType() &&
                null === $lineItemEntity->getParentId()) {
                return true;
            }
        } catch (Exception $exception) {
            // Catch class not found if SwagCustomizedProducts plugin is not installed
        }

        return false;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItemEntity
     */
    private function getLineItemRequest(
        $lineItemEntity,
        int $quantity
    ): array {
        $productNumber = is_array($lineItemEntity->getPayload()) && array_key_exists('productNumber', $lineItemEntity->getPayload())
            ? $lineItemEntity->getPayload()['productNumber']
            : $lineItemEntity->getIdentifier();

        $taxes = $lineItemEntity->getPrice() !== null ? $lineItemEntity->getPrice()->getCalculatedTaxes() : null;

        $taxRate = null === $taxes || null === $taxes->first()
            ? 0.0
            : $taxes->first()->getTaxRate();

        return $this->getRequestItem(
            $this->mapItemType($lineItemEntity->getType()),
            $productNumber,
            $lineItemEntity->getLabel(),
            $lineItemEntity instanceof LineItem ? $lineItemEntity->getPrice()->getUnitPrice() : $lineItemEntity->getUnitPrice(),
            $quantity,
            $taxRate
        );
    }

    /**
     * @param DeliveryCollection|OrderDeliveryCollection $deliveryCollection
     */
    private function getShippingItems(
        $deliveryCollection,
        string $languageId,
        ?Context $context = null
    ): array {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        /** @var Delivery|OrderDeliveryEntity $deliveryEntity */
        $deliveryEntity = $deliveryCollection->first();

        if ($deliveryEntity === null) {
            return [];
        }

        $shippingCosts = $deliveryEntity->getShippingCosts();

        if ($shippingCosts->getCalculatedTaxes()->count() == 0 || $shippingCosts->getTotalPrice() <= 0) {
            return [];
        }

        $languages = $context->getLanguageIdChain();

        if (!in_array($languageId, $languages, true)) {
            array_splice($languages, 0, 0, $languageId);

            $context->assign(['languageIdChain' => $languages]);
        }

        if ($deliveryEntity instanceof OrderDeliveryEntity) {
            $shippingMethod = $this->shipmentRepository->search(new Criteria([$deliveryEntity->getShippingMethodId()]), $context)->first();
        } elseif ($deliveryEntity instanceof Delivery) {
            $shippingMethod = $deliveryEntity->getShippingMethod();
        }

        /** @var null|ShippingMethodEntity $shippingMethod */
        if ($shippingMethod === null) {
            return [];
        }

        $items = [];
        foreach ($shippingCosts->getCalculatedTaxes() as $shipmentPosition) {
            /** @var CalculatedTax $shipmentPosition */
            $items[] = $this->getRequestItem(
                self::TYPE_SHIPMENT,
                '', // got be filled by `addShippingItemsToItemList`
                $shippingMethod->getName(),
                $shipmentPosition->getPrice(),
                1,
                $shipmentPosition->getTaxRate()
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

            if ($item['it'] === self::TYPE_SHIPMENT && empty($item['id'])) {
                // add product number for shipping (just the index of the item in the list)
                $item['id'] = $index;
            }

            // round/format price and tax amount
            $item['pr'] = $this->currencyPrecision->getRoundedItemAmount($item['pr'], $currency);
            $item['va'] = $this->currencyPrecision->getRoundedItemAmount($item['va'], $currency);

            if ($item['pr'] <= 0) {
                // price is does not have a positive price - skip it
                continue;
            }

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
        float $itemTaxRate
    ): array {
        return [
            'it' => $itemType,
            'id' => $itemNumber,
            'pr' => $itemPrice,
            'no' => $itemQty,
            'de' => $itemName,
            'va' => $itemTaxRate,
        ];
    }
}
