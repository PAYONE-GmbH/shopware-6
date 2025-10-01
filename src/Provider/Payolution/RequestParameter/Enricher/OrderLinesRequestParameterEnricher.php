<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\RequestParameter\Enricher\OrderLinesRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

readonly class OrderLinesRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use OrderLinesRequestParameterEnricherTrait;

    public function __construct(
        protected LineItemHydratorInterface $lineItemHydrator,
    ) {
    }
}
