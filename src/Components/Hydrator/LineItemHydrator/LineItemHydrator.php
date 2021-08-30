<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Exception;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class LineItemHydrator implements LineItemHydratorInterface
{
    public const TYPE_GOODS   = 'goods';
    public const TYPE_VOUCHER = 'voucher';

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->currencyPrecision = $currencyPrecision;
    }

    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderLineItemCollection $orderLineItems,
        array $requestLines
    ): array {
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

            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getLineItemRequest(
                    ++$counter,
                    $lineItem,
                    $currency,
                    $taxes->first(),
                    $orderLine['quantity']
                )
            );
        }

        return $requestLineItems;
    }

    public function mapOrderLines(CurrencyEntity $currency, OrderLineItemCollection $lineItemCollection): array
    {
        $requestLineItems = [];
        $counter          = 0;

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            if ($this->isCustomizedProduct($lineItem)) {
                continue;
            }

            $taxes = $lineItem->getPrice() !== null ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            if (null === $taxes || null === $taxes->first()) {
                continue;
            }

            $requestLineItems = array_merge(
                $requestLineItems,
                $this->getLineItemRequest(
                    ++$counter,
                    $lineItem,
                    $currency,
                    $taxes->first(),
                    $lineItem->getQuantity()
                )
            );
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

    private function getLineItemRequest(int $index, OrderLineItemEntity $lineItemEntity, CurrencyEntity $currencyEntity, CalculatedTax $calculatedTax, int $quantity): array
    {
        $productNumber = is_array($lineItemEntity->getPayload()) && array_key_exists('productNumber', $lineItemEntity->getPayload())
            ? $lineItemEntity->getPayload()['productNumber']
            : $lineItemEntity->getIdentifier();

        return [
            'it[' . $index . ']' => $this->mapItemType($lineItemEntity->getType()),
            'id[' . $index . ']' => $productNumber,
            'pr[' . $index . ']' => $this->currencyPrecision->getRoundedItemAmount($lineItemEntity->getUnitPrice(), $currencyEntity),
            'no[' . $index . ']' => $quantity,
            'de[' . $index . ']' => $lineItemEntity->getLabel(),
            'va[' . $index . ']' => $this->currencyPrecision->getRoundedItemAmount($calculatedTax->getTaxRate(), $currencyEntity),
        ];
    }
}
