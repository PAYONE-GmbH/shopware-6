<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Exception;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
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
        $counter          = 0;

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

            $taxes = $lineItem->getPrice() !== null ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            $taxRate = null === $taxes || null === $taxes->first()
                ? 0.0
                : $taxes->first()->getTaxRate();

            if (empty($orderLine['quantity'])) {
                continue;
            }

            $this->addLineItemRequest(
                $requestLineItems,
                $counter,
                $lineItem,
                $currency,
                $taxRate,
                $orderLine['quantity']
            );
        }

        if ($includeShippingCosts !== true) {
            return $requestLineItems;
        }

        if ($deliveries = $order->getDeliveries()) {
            $this->addShippingItems($requestLineItems, $counter, $deliveries, $order->getCurrency(), $order->getLanguageId());
        }

        return $requestLineItems;
    }

    public function mapCartLines(Cart $cart, SalesChannelContext $salesChannelContext): array
    {
        $requestLineItems = [];
        $counter          = 0;

        foreach ($cart->getLineItems() as $lineItem) {
            //if ($this->isCustomizedProduct($lineItem)) { // TODO verify if this is required
            //    continue;
            //}

            $taxes = $lineItem->getPrice() !== null ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            $taxRate = null === $taxes || null === $taxes->first()
                ? 0.0
                : $taxes->first()->getTaxRate();

            $this->addLineItemRequest(
                $requestLineItems,
                $counter,
                $lineItem,
                $salesChannelContext->getCurrency(),
                $taxRate,
                $lineItem->getQuantity()
            );
        }

        if ($deliveries = $cart->getDeliveries()) {
            $this->addShippingItems(
                $requestLineItems,
                $counter,
                $deliveries,
                $salesChannelContext->getCurrency(),
                $salesChannelContext->getLanguageId(),
                $salesChannelContext->getContext()
            );
        }

        return $requestLineItems;
    }

    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, Context $context): array
    {
        $lineItemCollection = $order->getLineItems();
        $requestLineItems   = [];
        $counter            = 0;

        if ($lineItemCollection === null) {
            return [];
        }

        foreach ($lineItemCollection as $lineItem) {
            if ($this->isCustomizedProduct($lineItem)) {
                continue;
            }

            $taxes = $lineItem->getPrice() !== null ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            $taxRate = null === $taxes || null === $taxes->first()
                ? 0.0
                : $taxes->first()->getTaxRate();

            $this->addLineItemRequest(
                $requestLineItems,
                $counter,
                $lineItem,
                $currency,
                $taxRate,
                $lineItem->getQuantity()
            );
        }

        if ($deliveries = $order->getDeliveries()) {
            $this->addShippingItems($requestLineItems, $counter, $deliveries, $order->getCurrency(), $order->getLanguageId());
        }

        return $requestLineItems;
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
    private function addLineItemRequest(
        array &$requestLineItems,
        int &$index,
        $lineItemEntity,
        CurrencyEntity $currencyEntity,
        float $taxRate,
        int $quantity
    ): void {
        $productNumber = is_array($lineItemEntity->getPayload()) && array_key_exists('productNumber', $lineItemEntity->getPayload())
            ? $lineItemEntity->getPayload()['productNumber']
            : $lineItemEntity->getIdentifier();

        $this->addRequestItem(
            $requestLineItems,
            $index,
            $this->mapItemType($lineItemEntity->getType()),
            $productNumber,
            $lineItemEntity->getLabel(),
            $lineItemEntity instanceof LineItem ? $lineItemEntity->getPrice()->getUnitPrice() : $lineItemEntity->getUnitPrice(),
            $quantity,
            $taxRate,
            $currencyEntity
        );
    }

    /**
     * @param DeliveryCollection|OrderDeliveryCollection $deliveryCollection
     */
    private function addShippingItems(
        array &$requestLineItems,
        int &$index,
        $deliveryCollection,
        CurrencyEntity $currencyEntity,
        string $languageId,
        ?Context $context = null
    ): void {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        $deliveryEntity = $deliveryCollection->first();

        if ($deliveryEntity === null) {
            return;
        }

        $shippingCosts = $deliveryEntity->getShippingCosts();

        if ($shippingCosts->getCalculatedTaxes()->count() <= 0
            || $this->currencyPrecision->getRoundedItemAmount($shippingCosts->getTotalPrice(), $currencyEntity) <= 0) {
            return;
        }

        $languages = $context->getLanguageIdChain();

        if (!in_array($languageId, $languages, true)) {
            array_splice($languages, 0, 0, $languageId);

            $context->assign(['languageIdChain' => $languages]);
        }

        $shippingMethod = $this->shipmentRepository->search(new Criteria([$deliveryEntity->getShippingMethodId()]), $context)->first();

        if ($shippingMethod === null) {
            return;
        }

        foreach ($shippingCosts->getCalculatedTaxes() as $shipmentPosition) {
            /** @var CalculatedTax $shipmentPosition */
            $this->addRequestItem(
                $requestLineItems,
                $index,
                self::TYPE_SHIPMENT,
                (string) ($index + 1),
                $shippingMethod->getName(),
                $shipmentPosition->getPrice(),
                1,
                $shipmentPosition->getTaxRate(),
                $currencyEntity
            );
        }
    }

    private function addRequestItem(
        array &$requestLineItems,
        int &$index,
        string $itemType,
        string $itemNumber,
        string $itemName,
        float $itemPrice,
        int $itemQty,
        float $itemTaxRate,
        CurrencyEntity $currency
    ): void {
        ++$index;
        $requestLineItems['it[' . $index . ']'] = $itemType;
        $requestLineItems['id[' . $index . ']'] = $itemNumber;
        $requestLineItems['pr[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($itemPrice, $currency);
        $requestLineItems['no[' . $index . ']'] = $itemQty;
        $requestLineItems['de[' . $index . ']'] = $itemName;
        $requestLineItems['va[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($itemTaxRate, $currency);
    }
}
