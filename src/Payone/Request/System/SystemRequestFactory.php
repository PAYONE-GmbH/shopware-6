<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Payone\Request\AbstractRequestFactory;

class SystemRequestFactory extends AbstractRequestFactory
{
    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(SystemRequest $systemRequest)
    {
        $this->systemRequest = $systemRequest;
    }

    public function getRequestParameters(string $salesChannel, string $configurationPrefix = ''): array
    {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $salesChannel,
            $configurationPrefix
        );

        return $this->createRequest();
    }
}
