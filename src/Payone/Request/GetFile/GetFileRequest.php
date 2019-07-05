<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\GetFile;

use PayonePayment\Installer\CustomFieldInstaller;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class GetFileRequest
{
    public function getRequestParameters(string $identification, Context $context): array
    {
        return [
            'request'        => 'getfile',
            'file_reference'           => $identification,
            'file_type' => 'SEPA_MANDATE',
            'file_format' => 'PDF',
        ];
    }
}
