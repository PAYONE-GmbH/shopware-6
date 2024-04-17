<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayoneSecuredInvoicePaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
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

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        if (empty($salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getPhoneNumber())) {
            $definitions['payonePhone'] = [new NotBlank()];
        }
        $definitions['payoneBirthday'] = [new NotBlank(), new Birthday()];

        return $definitions;
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            'financingType' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIV,

            // Store clearing bank account information as custom field of the transaction in order to
            // use this data for payment instructions of an invoice or similar.
            // See: https://docs.payone.com/display/public/PLATFORM/How+to+use+JSON-Responses#HowtouseJSON-Responses-JSON,Clearing-Data
            'clearingBankAccount' => array_merge(array_filter($response['clearing']['BankAccount'] ?? []), [
                // The PAYONE transaction ID acts as intended purpose of the transfer.
                // We add this field explicitly here to make clear that the transaction ID is used
                // as payment reference in context of the prepayment.
                'Reference' => (string) $response['txid'],
            ]),
        ];
    }
}
