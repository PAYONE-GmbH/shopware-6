<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter;

use PayonePayment\RequestParameter\AbstractRequestParameterEnricher;
use PayonePayment\RequestParameter\PaymentRequestEnricherTrait;

/**
 * @extends AbstractRequestParameterEnricher<CalculateRequestDto|ProfileRequestDto>
 */
readonly class RequestEnricher extends AbstractRequestParameterEnricher
{
    /**
     * @use PaymentRequestEnricherTrait<CalculateRequestDto|ProfileRequestDto>
     */
    use PaymentRequestEnricherTrait;
}
