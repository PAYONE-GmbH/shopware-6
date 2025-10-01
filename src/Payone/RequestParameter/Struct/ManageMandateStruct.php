<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManageMandateStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        protected string $iban,
        protected string $bic,
        string $paymentMethod,
    ) {
        $this->salesChannelContext = $salesChannelContext;
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
