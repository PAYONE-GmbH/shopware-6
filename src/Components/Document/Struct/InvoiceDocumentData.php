<?php

declare(strict_types=1);

namespace PayonePayment\Components\Document\Struct;

use Shopware\Core\Framework\Struct\Struct;

class InvoiceDocumentData extends Struct
{
    final public const EXTENSION_NAME = 'payone_document';

    protected string $iban = '';

    protected string $bic = '';

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getBic(): string
    {
        return $this->bic;
    }
}
