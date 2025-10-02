<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

/**
 * @extends AbstractRequestParameterEnricher<PaymentRequestDto>
 */
readonly class PaymentRequestEnricher extends AbstractRequestParameterEnricher
{
    /**
     * @use PaymentRequestEnricherTrait<PaymentRequestDto>
     */
    use PaymentRequestEnricherTrait;
}
