<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\RequestContentStruct;
use Shopware\Core\Framework\Context;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;

class RequestParameterFactory {
    /** @var iterable<AbstractRequestParameterBuilder> */
    private $requestParameterBuilder;

    public function __construct(iterable $requestParameterBuilder) {
        $this->requestParameterBuilder = $requestParameterBuilder;
    }

    public function getRequestParameter(RequestContentStruct $requestContent, Context $context) : array {
        $requestParameterBuilder = $this->getParameterBuilder($requestContent);

        $requestParameterBuilder->validate($requestContent);

        return $requestParameterBuilder->getRequestParameter($requestContent, $context);
    }

    private function getParameterBuilder(RequestContentStruct $requestContent) : AbstractRequestParameterBuilder {
        foreach($this->requestParameterBuilder as $builder) {
            if($builder->supports($requestContent) === true) {
                return $builder;
            }
        }

        throw new ClassNotFoundError('No valid request parameter builder found');
    }
}
