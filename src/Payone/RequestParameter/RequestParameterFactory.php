<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\ClientApiRequest;
use PayonePayment\Payone\RequestParameter\Struct\GetFileStruct;

class RequestParameterFactory
{
    private const BLACKLISTED_FIELDS = [
        'key',
        'hash',
        'integrator_name',
        'integrator_version',
        'solution_name',
        'solution_version',
    ];

    /**
     * @var iterable<AbstractRequestParameterBuilder>
     */
    private iterable $requestParameterBuilder;

    public function __construct(iterable $requestParameterBuilder)
    {
        $this->requestParameterBuilder = $requestParameterBuilder;
    }

    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $collectedParameters = [];

        foreach ($this->requestParameterBuilder as $builder) {
            if ($builder->supports($arguments) === true) {
                $collectedParameters[] = $builder->getRequestParameter($arguments);
            }
        }

        $parameters = array_merge(...$collectedParameters);

        if (empty($parameters)) {
            throw new \RuntimeException('No valid request parameter builder found');
        }

        $parameters = $this->createRequest($parameters);

        return $this->filterParams($arguments, $parameters);
    }

    private function filterParams(AbstractRequestParameterStruct $arguments, array $parameters): array
    {
        if ($arguments instanceof GetFileStruct) {
            unset($parameters['aid'], $parameters['hash']);
        }

        if ($arguments instanceof ClientApiRequest) {
            unset($parameters['key']);
        } else {
            unset($parameters['hash']);
        }

        return $parameters;
    }

    private function createRequest(array $parameters): array
    {
        ksort($parameters, \SORT_NATURAL | \SORT_FLAG_CASE);

        if (empty($parameters['key'])) {
            return $parameters;
        }

        $this->generateParameterHash($parameters);
        $parameters['key'] = hash('md5', $parameters['key']);

        return array_filter($parameters, static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function generateParameterHash(array &$parameters): void
    {
        $data = $parameters;

        foreach (self::BLACKLISTED_FIELDS as $field) {
            unset($data[$field]);
        }

        $parameters['hash'] = strtolower(hash_hmac('sha384', implode('', $data), $parameters['key']));
    }
}
