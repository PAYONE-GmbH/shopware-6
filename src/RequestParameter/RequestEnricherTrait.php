<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

/**
 * @template T of AbstractRequestDto
 */
trait RequestEnricherTrait
{
    /**
     * @param T $requestDto
     */
    protected function collect(AbstractRequestDto $requestDto, RequestParameterEnricherChain $enrichers): array
    {
        $collectedParameters = [];

        foreach ($enrichers->getElements() as $enricher) {
            $collectedParameters[] = $enricher->enrich($requestDto);
        }

        return \array_merge(...$collectedParameters);
    }

    /**
     * @param T $requestDto
     */
    protected function filter(mixed $requestDto, array $parameters): array
    {
        $key = $requestDto->clientApiRequest ? 'key' : 'hash';

        unset($parameters[$key]);

        return $parameters;
    }
}
