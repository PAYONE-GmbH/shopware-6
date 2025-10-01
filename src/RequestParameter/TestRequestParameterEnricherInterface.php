<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

interface TestRequestParameterEnricherInterface extends RequestParameterEnricherInterface
{
    public function isActive(): bool;

    public function getPaymentHandlerIdentifier(): string;

    public function getParameters(): array;
}
