<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\Request\RequestConstantsEnum;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Blank;

trait BasicValidationDefinitionTrait
{
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        return [
            RequestConstantsEnum::WORK_ORDER_ID->value => [ new Blank() ], // workorder-id is mostly not required.
            RequestConstantsEnum::CART_HASH->value     => [ new Blank() ], // cart-hash is mostly not required.
        ];
    }
}
