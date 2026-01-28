<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Dto\SecuredInvoice;

readonly class InvoiceDocumentDataDto
{
    public function __construct(
        public string|null $accountHolder,
        public string|null $iban,
        public string|null $bic,
        public string|null $dueDate,
        public string|null $reference,
    ) {
    }
}
