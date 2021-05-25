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

        return $parameters;
    }
}
