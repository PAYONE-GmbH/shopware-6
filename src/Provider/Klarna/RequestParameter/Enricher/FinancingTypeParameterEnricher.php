<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\RequestParameter\Enricher;

use PayonePayment\Provider\Klarna\ResponseHandler\FinancingTypeAwareInterface;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class FinancingTypeParameterEnricher implements RequestParameterEnricherInterface
{
    public function enrich(AbstractRequestDto $arguments): array
    {
        if (!$arguments->paymentHandler instanceof FinancingTypeAwareInterface) {
            throw new \RuntimeException('invalid payment method');
        }

        return [
            'financingtype' => $arguments->paymentHandler->getFinancingType()->value,
        ];
    }
}
