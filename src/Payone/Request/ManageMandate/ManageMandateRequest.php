<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use PayonePayment\Payone\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class ManageMandateRequest
{
    public function getRequestParameters(
        string $iban,
        string $bic
    ): array {
        return [
            'request'      => 'managemandate',
            'clearingtype' => 'elv',
            'iban'         => $iban,
            'bic'          => $bic,
        ];
    }
}
