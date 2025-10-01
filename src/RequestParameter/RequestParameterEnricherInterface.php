<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

/**
 * @template T of AbstractRequestDto
 */
interface RequestParameterEnricherInterface
{
    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array;
}
