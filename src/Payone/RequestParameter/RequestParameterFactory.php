<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;

class RequestParameterFactory {
    /** @var iterable<AbstractRequestParameterBuilder> */
    private $requestParameterBuilder;

    public function __construct(iterable $requestParameterBuilder) {
        $this->requestParameterBuilder = $requestParameterBuilder;
    }

    public function getRequestParameter(PaymentTransaction $paymentTransaction, RequestDataBag $requestData, SalesChannelContext $salesChannelContext) : array {
        $requestParameterBuilder = $this->getParameterBuilder($requestContent);

        $parameters = [];

        foreach($this->requestParameterBuilder as $builder) {
            if($builder->supports($requestContent) === true) {
                $builder->validate($requestContent);
                $parameters[] = $builder->getRequestParameter($requestContent, $context);
            }
        }

        if(empty($parameters)) {
            throw new ClassNotFoundError('No valid request parameter builder found');
        }

        return $parameters;
    }
}
