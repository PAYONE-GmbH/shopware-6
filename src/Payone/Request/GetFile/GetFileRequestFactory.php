<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\GetFile;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GetFileRequestFactory extends AbstractRequestFactory
{
    /** @var GetFileRequest */
    private $fileRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        GetFileRequest $fileRequest,
        SystemRequest $systemRequest
    ) {
        $this->fileRequest   = $fileRequest;
        $this->systemRequest = $systemRequest;
    }

    public function getRequestParameters(string $identification, SalesChannelContext $context): array
    {
        $this->requests[] = $this->fileRequest->getRequestParameters(
            $identification,
            $context->getContext()
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_DEBIT
        );

        $request = $this->createRequest();

        unset($request['aid']);

        return $request;
    }
}
