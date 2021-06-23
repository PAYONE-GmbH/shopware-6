<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManageMandateStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    /** @var string */
    protected $iban;

    /** @var string */
    protected $bic;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        string $iban,
        string $bic,
        string $paymentMethod
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->iban                = $iban;
        $this->bic                 = $bic;
        $this->paymentMethod       = $paymentMethod;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getBic(): string
    {
        return $this->bic;
    }
}
