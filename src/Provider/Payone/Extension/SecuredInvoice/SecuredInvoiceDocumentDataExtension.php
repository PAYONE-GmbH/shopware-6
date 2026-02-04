<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Extension\SecuredInvoice;

use Shopware\Core\Framework\Struct\Struct;

class SecuredInvoiceDocumentDataExtension extends Struct
{
    final public const EXTENSION_NAME = 'payone_secured_invoice_data';

    public function __construct(
        public string|null $accountHolder,
        public string|null $iban,
        public string|null $bic,
        public string|null $dueDate,
        public string|null $reference,
    ) {
    }
}