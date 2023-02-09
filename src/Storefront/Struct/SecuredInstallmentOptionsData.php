<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class SecuredInstallmentOptionsData extends Struct
{
    public const EXTENSION_NAME = 'payoneSecuredInstallmentOptions';

    /**
     * @var array<SecuredInstallmentOption>
     */
    protected array $options;

    public function getOptions(): array
    {
        return $this->options;
    }
}
