<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

abstract class AbstractRequestFactory
{
    private const BLACKLISTED_FIELDS = [
        'key',
        'hash',
        'integrator_name',
        'integrator_version',
        'solution_name',
        'solution_version',
    ];

    /** @var array[] */
    protected $requests;

    protected function createRequest()
    {
        $parameters = [];

        foreach ($this->requests as $request) {
            $parameters = array_merge($parameters, $request);
        }

        // Clear requests to allow for multiple calls
        $this->requests = [];

        ksort($parameters, SORT_NATURAL | SORT_FLAG_CASE);

        if (empty($parameters['key'])) {
            return $parameters;
        }

        $parameters['hash'] = $this->generateParameterHash($parameters);
        $parameters['key']  = hash('md5', $parameters['key']);

        return $parameters;
    }

    private function generateParameterHash(array $parameters): string
    {
        $data = $parameters;

        foreach (self::BLACKLISTED_FIELDS as $field) {
            unset($data[$field]);
        }

        return strtolower(hash_hmac('sha384', implode('', $data), $parameters['key']));
    }
}
