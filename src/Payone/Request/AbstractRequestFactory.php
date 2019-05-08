<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

abstract class AbstractRequestFactory
{
    /** @var array[] */
    protected $requests;

    protected function createRequest()
    {
        $parameters = [];

        foreach ($this->requests as $request) {
            $parameters += $request;
        }

        ksort($parameters, SORT_NATURAL | SORT_FLAG_CASE);

        $parameters['hash'] = $this->generateParameterHash($parameters);

        return $parameters;
    }

    private function generateParameterHash(array $parameters): string
    {
        $data = $parameters;

        unset($data['key'], $data['hash']);

        return strtolower(hash_hmac(
            'sha384',
            implode('', $data),
            $parameters['key']
        ));
    }
}
