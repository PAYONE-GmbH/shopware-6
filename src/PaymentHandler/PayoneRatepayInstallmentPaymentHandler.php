<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayoneRatepayInstallmentPaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        $definitions['payonePhone'] = [new NotBlank()];
        $definitions['ratepayBirthday'] = [new NotBlank(), new Birthday()];
        $definitions['ratepayIban'] = [new Iban()];

        $definitions['ratepayInstallmentAmount'] = [new NotBlank()];
        $definitions['ratepayInstallmentNumber'] = [new NotBlank()];
        $definitions['ratepayLastInstallmentAmount'] = [new NotBlank()];
        $definitions['ratepayInterestRate'] = [new NotBlank()];
        $definitions['ratepayTotalAmount'] = [new NotBlank()];

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

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        // It differs depending on the authorization method
        $clearingReference = $response['addpaydata']['clearing_reference'] ?? $response['clearing']['Reference'];

        return [
            'workOrderId' => $dataBag->get('workorder'),
            'clearingReference' => $clearingReference,
            'captureMode' => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            'financingType' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
            'additionalData' => ['used_ratepay_shop_id' => $request['add_paydata[shop_id]']],
        ];
    }
}
