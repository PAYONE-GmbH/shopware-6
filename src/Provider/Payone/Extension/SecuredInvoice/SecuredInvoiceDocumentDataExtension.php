<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Extension\SecuredInvoice;

use PayonePayment\Provider\Payone\Dto\SecuredInvoice\InvoiceDocumentDataDto;
use Shopware\Core\Framework\Struct\Struct;

class SecuredInvoiceDocumentDataExtension extends Struct
{
    final public const EXTENSION_NAME = 'payone_secured_invoice_data';

    private array $documentData = [];

    public function __construct()
    {
    }

    public function addDocumentData(InvoiceDocumentDataDto $documentData): void
    {
        $this->documentData[] = $documentData;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function setDocumentData(array $documentData): void
    {
        $this->documentData = $documentData;
    }
}