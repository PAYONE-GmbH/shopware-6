<?php

declare(strict_types=1);

namespace PayonePayment\Components\Hydrator\LineItemHydrator;

use Exception;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\System\Currency\CurrencyEntity;

class LineItemHydrator implements LineItemHydratorInterface
{
    public const TYPE_GOODS   = 'goods';
    public const TYPE_VOUCHER = 'voucher';

    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderLineItemCollection $orderLineItems,
        array $requestLines
    ): array {
        $requestLineItems = [];
        $counter          = 1;

        foreach ($requestLines as $orderLine) {
            if (!array_key_exists('id', $orderLine)) {
                continue;
            }

            $lineItem = $orderLineItems->get($orderLine['id']);

            if ($lineItem === null) {
                continue;
            }

            try {
                /** @phpstan-ignore-next-line */
                if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector') &&
                    CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItem->getType(
                    ) &&
                    null === $lineItem->getParentId()) {
                    continue;
                }
            } catch (Exception $exception) {
                // Catch class not found if SwagCustomizedProducts plugin is not installed
            }

            $taxes = $lineItem->getPrice() ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            if (null === $taxes || null === $taxes->first()) {
                continue;
            }
            $requestLineItems['it[' . $counter . ']'] = $this->mapItemType($lineItem->getType());
            $requestLineItems['id[' . $counter . ']'] = $lineItem->getIdentifier();
            $requestLineItems['pr[' . $counter . ']'] = (int) round(
                ($lineItem->getUnitPrice() * (10 ** $currency->getDecimalPrecision()))
            );
            $requestLineItems['no[' . $counter . ']'] = $orderLine['quantity'];
            $requestLineItems['de[' . $counter . ']'] = $lineItem->getLabel();
            $requestLineItems['va[' . $counter . ']'] = (int) round(
                ($taxes->first()->getTaxRate() * (10 ** $currency->getDecimalPrecision()))
            );
            ++$counter;
        }

        return $requestLineItems;
    }

    public function mapOrderLines(CurrencyEntity $currency, OrderLineItemCollection $lineItemCollection): array
    {
        $requestLineItems = [];

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            try {
                /** @phpstan-ignore-next-line */
                if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector') &&
                    CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItem->getType(
                    ) &&
                    null === $lineItem->getParentId()) {
                    continue;
                }
            } catch (Exception $exception) {
                // Catch class not found if SwagCustomizedProducts plugin is not installed
            }

            $taxes = $lineItem->getPrice() ? $lineItem->getPrice()->getCalculatedTaxes() : null;

            if (null === $taxes || null === $taxes->first()) {
                continue;
            }

            $counter                                  = count($requestLineItems) + 1;
            $requestLineItems['it[' . $counter . ']'] = $this->mapItemType($lineItem->getType());
            $requestLineItems['id[' . $counter . ']'] = $lineItem->getIdentifier();
            $requestLineItems['pr[' . $counter . ']'] = (int) round(
                ($lineItem->getUnitPrice() * (10 ** $currency->getDecimalPrecision()))
            );
            $requestLineItems['no[' . $counter . ']'] = $lineItem->getQuantity();
            $requestLineItems['de[' . $counter . ']'] = $lineItem->getLabel();
            $requestLineItems['va[' . $counter . ']'] = (int) round(
                ($taxes->first()->getTaxRate() * (10 ** $currency->getDecimalPrecision()))
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
}
