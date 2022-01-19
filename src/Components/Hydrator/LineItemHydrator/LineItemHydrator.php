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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemHydrator implements LineItemHydratorInterface
{
    public const TYPE_GOODS    = 'goods';
    public const TYPE_VOUCHER  = 'voucher';
    public const TYPE_SHIPMENT = 'shipment';

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    /** @var TranslatorInterface */
    private $translator;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision, TranslatorInterface $translator, EntityRepositoryInterface $languageRepository)
    {
        $this->currencyPrecision  = $currencyPrecision;
        $this->translator         = $translator;
        $this->languageRepository = $languageRepository;
    }

    public function mapPayoneOrderLinesByRequest(
        CurrencyEntity $currency,
        OrderEntity $order,
        array $requestLines,
        bool $includeShippingCosts
    ): array {
        $orderLineItems = $order->getLineItems();

        if (empty($orderLineItems)) {
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

        if ($includeShippingCosts === true) {
            $requestLineItems = $this->addShippingItems($order, $counter, $requestLineItems, $currency);
        }

        return $requestLineItems;
    }

    /** @noinspection SlowArrayOperationsInLoopInspection */
    public function mapOrderLines(CurrencyEntity $currency, OrderEntity $order, SalesChannelContext $salesChannelContext): array
    {
        $lineItemCollection = $order->getLineItems();

        if (empty($lineItemCollection)) {
            return [];
        }

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

        return $this->addShippingItems($order, $counter, $requestLineItems, $currency, $salesChannelContext);
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

    private function addShippingItems(
        OrderEntity $order,
        int $index,
        array $lineItems,
        CurrencyEntity $currencyEntity,
        ?SalesChannelContext $salesChannelContext = null
    ): array {
        $shippingCosts = $order->getShippingCosts();
        $locale        = $order->getLanguage()->getLocale();

        if (null !== $locale) {
            $localeCode = $locale->getCode();
        }

        if (null === $locale) {
            $localeCode = $this->getLocaleCode($salesChannelContext);
        }

        if ($shippingCosts->getTotalPrice() < 0.01 || $shippingCosts->getCalculatedTaxes()->count() <= 0) {
            return $lineItems;
        }

        foreach ($shippingCosts->getCalculatedTaxes() as $shipmentPosition) {
            /** @var CalculatedTax $shipmentPosition */
            ++$index;

            $lineItems = array_merge(
                $lineItems,
                [
                    'it[' . $index . ']' => self::TYPE_SHIPMENT,
                    'id[' . $index . ']' => $index,
                    'pr[' . $index . ']' => $this->currencyPrecision->getRoundedItemAmount($shipmentPosition->getPrice(), $currencyEntity),
                    'no[' . $index . ']' => 1,
                    'de[' . $index . ']' => $this->translator->trans('PayonePayment.general.shippingCosts', [], null, $localeCode),
                    'va[' . $index . ']' => $this->currencyPrecision->getRoundedItemAmount($shipmentPosition->getTaxRate(), $currencyEntity),
                ]
            );
        }

        return $lineItems;
    }

    private function getLocaleCode(?SalesChannelContext $salesChannelContext): string
    {
        /**
         * locale might be null, the shipment items are only required for secured invoice and payolution payment methods.
         * those are only available in DACH. because of this we do use de-DE as fallback locale
         */
        if (null === $salesChannelContext) {
            return 'de-DE';
        }

        $criteria = new Criteria([$salesChannelContext->getContext()->getLanguageId()]);
        $criteria->addAssociation('locale');
        $language = $this->languageRepository->search($criteria, $salesChannelContext->getContext())->first();

        if (null === $language || null === $language->getLocale()) {
            return 'de-DE';
        }

        return $language->getLocale()->getCode();
    }
}
