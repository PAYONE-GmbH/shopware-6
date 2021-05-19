<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Struct\Struct;

abstract class AbstractRequestParameterBuilder {
    protected RedirectHandler $redirectHandler;

    abstract public function getRequestParameter(Struct $requestContent) : array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(Struct $requestContent) : bool;

    /**
     * Validate if given content does provide all necessary information
     *
     * @throws InvalidRequestParameterException
     */
    abstract public function validate(Struct $requestContent) : void;

    protected function getConvertedAmount(float $amount, int $precision) : int {
        return $amount * (10 ** $precision);
    }

    protected function encodeUrl(string $url) : string {
        return $this->redirectHandler->encode($url);
    }
}
