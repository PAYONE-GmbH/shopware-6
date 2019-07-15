<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

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
