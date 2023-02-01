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
            $definitions['payoneInvoiceBirthday'] = [new NotBlank(), new Birthday(['value' => $this->getMinimumDate()])];
        }

        return $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigKey(): string
    {
        return 'secureInvoiceAuthorizationMethod';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentMethod(): string
    {
        return __CLASS__;
    }
}
