<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher;

use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;

/**
 * @extends OrderLinesRequestParameterEnricher<PaymentRequestDto>
 */
readonly class OrderLinesWithNegativePriceRequestParameterEnricher extends OrderLinesRequestParameterEnricher
{
    public function enrich(AbstractRequestDto $arguments): array
    {
        $parameters = parent::enrich($arguments);

        if (RequestActionEnum::REFUND->value !== $arguments->action) {
            return $parameters;
        }

        foreach ($parameters as $key => &$parameter) {
            if (\str_starts_with($key, 'pr[')) {
                $parameter *= -1;
            }
        }

        return $parameters;
    }
}
