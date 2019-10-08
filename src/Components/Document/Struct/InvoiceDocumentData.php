<?php

declare(strict_types=1);

namespace PayonePayment\Components\Document\Struct;

use Shopware\Core\Framework\Struct\Struct;

class InvoiceDocumentData extends Struct
{
    public const EXTENSION_NAME = 'payone_document';

    /** @var string */
    protected $iban = '';

    /** @var string */
    protected $bic = '';

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getBic(): string
    {
        return $this->bic;
    }
}
