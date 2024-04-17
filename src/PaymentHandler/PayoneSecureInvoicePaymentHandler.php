<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayoneSecureInvoicePaymentHandler extends AbstractPayoneInvoicePaymentHandler
{
    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        if (!$this->customerHasCompanyAddress($salesChannelContext)) {
            $definitions['payoneBirthday'] = [new NotBlank(), new Birthday()];
        }

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }
}
