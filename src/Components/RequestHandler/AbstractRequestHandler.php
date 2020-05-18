<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestHandler;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractRequestHandler
{
    abstract public function supports(string $paymentMethodId): bool;

    abstract public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array;

    protected function mapPayoneOrderLines(CurrencyEntity $currency, OrderLineItemCollection $orderLineItems, array $requestLines = null): array
    {
        $requestLineItems = [];
        $counter = 0;

        if (empty($requestLines)) {
            foreach ($orderLineItems as $lineItem) {
                $taxes = $lineItem->getPrice() ? $lineItem->getPrice()->getCalculatedTaxes() : null;

                if(null === $taxes || null === $taxes->first()) {
                    continue;
                }

                $requestLineItems['it['.$counter.']'] = $this->mapItemType($lineItem->getType());
                $requestLineItems['id['.$counter.']'] = $lineItem->getIdentifier();
                $requestLineItems['pr['.$counter.']'] = (int) ($lineItem->getUnitPrice() * (10 ** $currency->getDecimalPrecision()));
                $requestLineItems['no['.$counter.']'] = $lineItem->getQuantity();
                $requestLineItems['de['.$counter.']'] = $lineItem->getLabel();
                $requestLineItems['va['.$counter.']'] = (int) ($taxes->first()->getTaxRate() * (10 ** $currency->getDecimalPrecision()));
                $counter++;
            }
        } else {
            foreach ($requestLines as $orderLine) {
                foreach ($orderLineItems as $lineItem) {
                    $taxes = $lineItem->getPrice() ? $lineItem->getPrice()->getCalculatedTaxes() : null;
                    if(null === $taxes || null === $taxes->first()) {
                        continue;
                    }

                    if($lineItem->getId() !== $orderLine['id']) {
                        continue;
                    }

                    $requestLineItems['it['.$counter.']'] = $this->mapItemType($lineItem->getType());
                    $requestLineItems['id['.$counter.']'] = $lineItem->getIdentifier();
                    $requestLineItems['pr['.$counter.']'] = (int) ($lineItem->getUnitPrice() * (10 ** $currency->getDecimalPrecision()));
                    $requestLineItems['no['.$counter.']'] = $orderLine['quantity'];
                    $requestLineItems['de['.$counter.']'] = $lineItem->getLabel();
                    $requestLineItems['va['.$counter.']'] = (int) ($taxes->first()->getTaxRate() * (10 ** $currency->getDecimalPrecision()));
                    $counter++;
                }
            }
        }

        return $requestLineItems;
    }

    protected function mapItemType(?string $itemType): string
    {
        switch ($itemType) {
            case 'shipment':
                return 'shipment';
            case 'handling':
                return 'handling';
            case 'voucher':
                return 'voucher';
            default:
                return 'goods';
        }
    }
}
