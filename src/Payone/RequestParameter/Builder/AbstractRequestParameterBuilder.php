<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use Shopware\Core\Framework\Struct\Struct;

abstract class AbstractRequestParameterBuilder
{
    public const REQUEST_ACTION_AUTHORIZE    = 'authorization';
    public const REQUEST_ACTION_PREAUTHORIZE = 'preauthorization';
    public const REQUEST_ACTION_TEST         = 'test';

    /** @param PaymentTransactionStruct|TestCredentialsStruct $arguments */
    abstract public function getRequestParameter(
        Struct $arguments
    ): array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */

    /** @param PaymentTransactionStruct|TestCredentialsStruct $arguments */
    abstract public function supports(Struct $arguments): bool;

    protected function getConvertedAmount(float $amount, int $precision): int
    {
        return (int) round($amount * (10 ** $precision));
    }
}
