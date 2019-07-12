<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\GetFile;

use PayonePayment\Payone\Request\AbstractRequestFactory;
use Shopware\Core\Framework\Context;

class GetFileRequestFactory extends AbstractRequestFactory
{
    /** @var GetFileRequest */
    private $fileRequest;

    public function __construct(GetFileRequest $fileRequest)
    {
        $this->fileRequest = $fileRequest;
    }

    public function getRequestParameters(string $identification, Context $context): array
    {
        $this->requests[] = $this->fileRequest->getRequestParameters(
            $identification,
            $context
        );

        return $this->createRequest();
    }
}
