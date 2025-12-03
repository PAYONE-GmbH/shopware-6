<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Przelewy24\RequestParameter\Enricher;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\RequestParameter\Enricher\OptionalOrderLinesRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @template T as PaymentRequestDto
 *
 * @implements RequestParameterEnricherInterface<T>
 */
readonly class OrderLinesRequestParameterEnricher implements RequestParameterEnricherInterface
{
    /**
     * @use OptionalOrderLinesRequestParameterEnricherTrait<T>
     */
    use OptionalOrderLinesRequestParameterEnricherTrait;

    public function __construct(
        protected LineItemHydratorInterface $lineItemHydrator,
        protected ConfigReader $configReader,
    ) {
    }
}
