<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PayoneIDealPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    /**
     * Valid iDEAL bank groups according to:
     * https://docs.payone.com/pages/releaseview.action?pageId=1213906
     */
    protected const VALID_IDEAL_BANK_GROUPS = [
        'ABN_AMRO_BANK',
        'ASN_BANK',
        'BUNQ_BANK',
        'ING_BANK',
        'KNAB_BANK',
        'RABOBANK',
        'REVOLUT',
        'SNS_BANK',
        'SNS_REGIO_BANK',
        'TRIODOS_BANK',
        'VAN_LANSCHOT_BANKIERS',
        'YOURSAFE',
        'NATIONALE_NEDERLANDEN',
        'N26',
    ];

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower((string)$transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_PAID) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    /**
     * @throws PayoneRequestException
     */
    protected function validateRequestData(RequestDataBag $dataBag): void
    {
        $bankGroup = $dataBag->get('idealBankGroup');

        if (!\in_array($bankGroup, static::VALID_IDEAL_BANK_GROUPS, true)) {
            throw new PayoneRequestException('No valid iDEAL bank group');
        }
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
