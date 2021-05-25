<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

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

    /** @var iterable<AbstractRequestParameterBuilder> */
    private $requestParameterBuilder;

    public function __construct(iterable $requestParameterBuilder)
    {
        $this->requestParameterBuilder = $requestParameterBuilder;
    }

    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ): array {
        $parameters = [];

        foreach ($this->requestParameterBuilder as $builder) {
            if ($builder->supports($paymentMethod, $action) === true) {
                $parameters = array_merge(
                    $parameters,
                    $builder->getRequestParameter($paymentTransaction, $requestData, $salesChannelContext, $paymentMethod, $action)
                );
            }
        }

        if (empty($parameters)) {
            throw new RuntimeException('No valid request parameter builder found');
        }

        $parameters['hash'] = $this->generateParameterHash($parameters);

        return $this->createRequest($parameters);
    }

    protected function createRequest(array $parameters) : array
    {
        ksort($parameters, SORT_NATURAL | SORT_FLAG_CASE);

        if (empty($parameters['key'])) {
            return $parameters;
        }

        $parameters['hash'] = $this->generateParameterHash($parameters);
        $parameters['key']  = hash('md5', $parameters['key']);

        return $parameters;
    }

    //TODO: carthashing service?
    protected function generateParameterHash(array $parameters): string
    {
        $data = $parameters;

        foreach (self::BLACKLISTED_FIELDS as $field) {
            unset($data[$field]);
        }

        return strtolower(hash_hmac('sha384', implode('', $data), $parameters['key']));
    }
}
