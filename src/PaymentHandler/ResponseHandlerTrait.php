<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\ResponseHandler\ResponseHandlerInterface;

trait ResponseHandlerTrait
{
    protected readonly ResponseHandlerInterface $responseHandler;

    public function getResponseHandler(): ResponseHandlerInterface
    {
        return $this->responseHandler;
    }
}
