<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use PayonePayment\DataAbstractionLayer\Entity\Mandate\PayonePaymentMandateEntity;
use Shopware\Core\Framework\Struct\Struct;

class CheckoutFinishPaymentData extends Struct
{
    public const EXTENSION_NAME = 'payone';

    /** @var null|PayonePaymentMandateEntity */
    protected $mandate;

    public function getMandate(): ?PayonePaymentMandateEntity
    {
        return $this->mandate;
    }
}
