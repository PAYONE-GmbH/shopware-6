<?php

declare(strict_types=1);

namespace PayonePayment\Components\DependencyInjection\Factory;

use PayonePayment\Components\Exception\NoRequestBuilderFoundException;
use PayonePayment\Components\RequestBuilder\AbstractRequestBuilder;

class RequestBuilderFactory
{
    /** @var AbstractRequestBuilder[]|iterable */
    protected $requestBuilderCollection = [];

    public function __construct(iterable $requestBuilderCollection)
    {
        $this->requestBuilderCollection = $requestBuilderCollection;
    }

    public function getRequestBuilder(string $paymentMethodId, string $orderNumber): AbstractRequestBuilder
    {
        foreach ($this->requestBuilderCollection as $requestBuilder) {
            if (!empty($paymentMethodId) && $requestBuilder->supports($paymentMethodId)) {
                return $requestBuilder;
            }
        }

        throw new NoRequestBuilderFoundException($orderNumber);
    }
}
