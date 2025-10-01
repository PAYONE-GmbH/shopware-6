<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\Request\RequestConstantsEnum;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

trait GenericExpressCheckoutTrait
{
    use IsCapturableTrait;
    use IsRefundableTrait;
    use StatusBasedRedirectResponseTrait;

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData)
            || static::matchesIsCapturableDefaults($transactionData)
        ;
    }

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        return [
            RequestConstantsEnum::WORK_ORDER_ID->value => [ new NotBlank() ],
            RequestConstantsEnum::CART_HASH->value     => [ new NotBlank() ],
        ];
    }
}
