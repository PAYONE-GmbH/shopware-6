<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;

/**
 * @template T of PaymentRequestDto
 */
trait OptionalOrderLinesRequestParameterEnricherTrait
{
    /**
     * @use OrderLinesRequestParameterEnricherTrait<T>
     */
    use OrderLinesRequestParameterEnricherTrait {
        OrderLinesRequestParameterEnricherTrait::enrich as enrichOrderLines;
    }

    protected readonly ConfigReader $configReader;

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        $config = $this->configReader->read($arguments->paymentTransaction->order->getSalesChannelId());

        if (!$config->get('submitOrderLineItems', false)) {
            return [];
        }

        return $this->enrichOrderLines($arguments);
    }
}
