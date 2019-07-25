<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Test;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;

class TestRequestFactory extends AbstractRequestFactory
{
    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(SystemRequest $systemRequest)
    {
        $this->systemRequest = $systemRequest;
    }

    public function getRequestParameters(?string $salesChannel, string $configurationPrefix = '', array $additionalParameters): array
    {
        $systemRequestParameters = $this->systemRequest->getRequestParameters(
            $salesChannel,
            $configurationPrefix
        );
        $systemRequestParameters['mode'] = 'test';

        $this->requests[] = $systemRequestParameters;
        $this->requests[] = $additionalParameters;

        $request        = $this->createRequest();
        $this->requests = [];

        return $request;
    }
}
