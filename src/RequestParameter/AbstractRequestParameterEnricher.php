<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @template T of AbstractRequestDto
 */
abstract readonly class AbstractRequestParameterEnricher
{
    /**
     * @param T $requestDto
     */
    final public function enrich(
        AbstractRequestDto $requestDto,
        RequestParameterEnricherChain $enrichers,
    ): RequestDataBag {
        $parameters = $this->collect($requestDto, $enrichers);

        if ([] === $parameters) {
            throw new \RuntimeException('No valid request parameter enricher found');
        }

        $parameters     = $this->createRequest($parameters);
        $filteredParams = $this->filter($requestDto, $parameters);

        return new RequestDataBag($filteredParams);
    }

    final protected function createRequest(array $parameters): array
    {
        \ksort($parameters, \SORT_NATURAL | \SORT_FLAG_CASE);

        if (empty($parameters['key'])) {
            return $parameters;
        }

        $this->generateParameterHash($parameters);

        $parameters['key'] = \hash('sha384', (string) $parameters['key']);

        return \array_filter($parameters, static fn ($value) => null !== $value && '' !== $value);
    }

    /**
     * @param T $requestDto
     */
    abstract protected function collect(
        AbstractRequestDto $requestDto,
        RequestParameterEnricherChain $enrichers,
    ): array;

    /**
     * @param T $requestDto
     */
    abstract protected function filter(AbstractRequestDto $requestDto, array $parameters): array;

    private function generateParameterHash(array &$parameters): void
    {
        $data = $parameters;

        foreach (FieldBlacklistEnum::cases() as $field) {
            unset($data[$field->value]);
        }

        $parameters['hash'] = \strtolower(\hash_hmac('sha384', \implode('', $data), (string) $parameters['key']));
    }
}
