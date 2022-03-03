<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Exception;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
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

            if (null === $taxes || null === $taxes->first()) {
                continue;
            }

            if (empty($orderLine['quantity'])) {
                continue;
            }

            $requestLineItems = $this->getLineItemRequest(
                $requestLineItems,
                ++$counter,
                $lineItem,
                $currency,
                $taxes->first(),
                $orderLine['quantity']
            );
        }

        if ($includeShippingCosts !== true) {
            return $requestLineItems;
        }

        return $this->addShippingItems($requestLineItems, $counter, $order, $currency);
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

            if (null === $taxes || null === $taxes->first()) {
                continue;
            }

            $requestLineItems = $this->getLineItemRequest(
                $requestLineItems,
                ++$counter,
                $lineItem,
                $currency,
                $taxes->first(),
                $lineItem->getQuantity()
            );
        }

        return $this->addShippingItems($requestLineItems, $counter, $order, $currency, $context);
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
                CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItemEntity->getType(
                ) &&
                null === $lineItemEntity->getParentId()) {
                return true;
            }
        } catch (Exception $exception) {
            // Catch class not found if SwagCustomizedProducts plugin is not installed
        }

        return false;
    }

    private function getLineItemRequest(
        array $requestLineItems,
        int $index,
        OrderLineItemEntity $lineItemEntity,
        CurrencyEntity $currencyEntity,
        CalculatedTax $calculatedTax,
        int $quantity
    ): array {
        $productNumber = is_array($lineItemEntity->getPayload()) && array_key_exists('productNumber', $lineItemEntity->getPayload())
            ? $lineItemEntity->getPayload()['productNumber']
            : $lineItemEntity->getIdentifier();

        $requestLineItems['it[' . $index . ']'] = $this->mapItemType($lineItemEntity->getType());
        $requestLineItems['id[' . $index . ']'] = $productNumber;
        $requestLineItems['pr[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($lineItemEntity->getUnitPrice(), $currencyEntity);
        $requestLineItems['no[' . $index . ']'] = $quantity;
        $requestLineItems['de[' . $index . ']'] = $lineItemEntity->getLabel();
        $requestLineItems['va[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($calculatedTax->getTaxRate(), $currencyEntity);

        return $requestLineItems;
    }

    private function addShippingItems(
        array $requestLineItems,
        int $index,
        OrderEntity $order,
        CurrencyEntity $currencyEntity,
        ?Context $context = null
    ): array {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }

        if ($order->getDeliveries() === null) {
            return $requestLineItems;
        }

        $deliveryEntity = $order->getDeliveries()->first();

        if ($deliveryEntity === null) {
            return $requestLineItems;
        }

        $shippingCosts = $deliveryEntity->getShippingCosts();

        if ($shippingCosts->getCalculatedTaxes()->count() <= 0
            || $this->currencyPrecision->getRoundedItemAmount($shippingCosts->getTotalPrice(), $currencyEntity) <= 0) {
            return $requestLineItems;
        }

        $languages = $context->getLanguageIdChain();

        if (!in_array($order->getLanguageId(), $languages, true)) {
            array_splice($languages, 0, 0, $order->getLanguageId());
        }

        $newContext = new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $context->getCurrencyId(),
            $languages
        );

        $shippingMethod = $this->shipmentRepository->search(new Criteria([$deliveryEntity->getShippingMethodId()]), $newContext)->first();

        if ($shippingMethod === null) {
            return $requestLineItems;
        }

        foreach ($shippingCosts->getCalculatedTaxes() as $shipmentPosition) {
            /** @var CalculatedTax $shipmentPosition */
            ++$index;

            $requestLineItems['it[' . $index . ']'] = self::TYPE_SHIPMENT;
            $requestLineItems['id[' . $index . ']'] = $index;
            $requestLineItems['pr[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($shipmentPosition->getPrice(), $currencyEntity);
            $requestLineItems['no[' . $index . ']'] = 1;
            $requestLineItems['de[' . $index . ']'] = $shippingMethod->getName();
            $requestLineItems['va[' . $index . ']'] = $this->currencyPrecision->getRoundedItemAmount($shipmentPosition->getTaxRate(), $currencyEntity);
        }

        return $requestLineItems;
    }
}
