<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManageMandateRequest
{
    public function getRequestParameters(
        SalesChannelContext $context,
        string $iban,
        string $bic
    ): array {
        return [
            'request'      => 'managemandate',
            'clearingtype' => 'elv',
            'iban'         => $iban,
            'bic'          => $bic,
            'currency'     => $context->getCurrency()->getIsoCode(),
        ];
    }
}
