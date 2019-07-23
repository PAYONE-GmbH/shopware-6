<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\GetFile;

use Shopware\Core\Framework\Context;

class GetFileRequest
{
    public function getRequestParameters(string $identification, Context $context): array
    {
        return [
            'request'        => 'getfile',
            'file_reference' => $identification,
            'file_type'      => 'SEPA_MANDATE',
            'file_format'    => 'PDF',
        ];
    }
}
