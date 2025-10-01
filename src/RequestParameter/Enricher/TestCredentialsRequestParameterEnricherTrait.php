<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\RequestParameter\AbstractRequestDto;

/**
 * @template T of AbstractRequestDto
 */
trait TestCredentialsRequestParameterEnricherTrait
{
    protected const REFERENCE_PREFIX_TEST = 'TESTPO-';

    public function isActive(): bool
    {
        return false;
    }

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        return [];
    }

    public function getParameters(): array
    {
        return $this->getTestCredentials();
    }

    protected function getReference(): string
    {
        return \sprintf(
            '%s%d',
            self::REFERENCE_PREFIX_TEST,
            \random_int(1_000_000_000_000, 9_999_999_999_999),
        );
    }

    abstract protected function getTestCredentials(): array;
}
