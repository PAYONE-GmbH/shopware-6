<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter;

use PayonePayment\RequestParameter\AbstractRequestParameterEnricher;
use PayonePayment\RequestParameter\RequestEnricherTrait;

/**
 * @extends AbstractRequestParameterEnricher<CreditCardCheckRequestDto>
 */
readonly class RequestEnricher extends AbstractRequestParameterEnricher
{
    /**
     * @use RequestEnricherTrait<CreditCardCheckRequestDto>
     */
    use RequestEnricherTrait;
}
