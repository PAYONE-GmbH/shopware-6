<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher;

use PayonePayment\RequestParameter\Enricher\ShippingInformationRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

readonly class ShippingInformationRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ShippingInformationRequestParameterEnricherTrait;
}
