<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PayoneEpsPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    /**
     * Valid iDEAL bank groups according to:
     * https://docs.payone.com/pages/releaseview.action?pageId=1213908
     */
    protected const VALID_EPS_BANK_GROUPS = [
        'ARZ_OAB',
        'ARZ_BAF',
        'BA_AUS',
        'ARZ_BCS',
        'EPS_SCHEL',
        'BAWAG_PSK',
        'BAWAG_ESY',
        'SPARDAT_EBS',
        'ARZ_HAA',
        'ARZ_VLH',
        'HRAC_OOS',
        'ARZ_HTB',
        'EPS_OBAG',
        'RAC_RAC',
        'EPS_SCHOELLER',
        'ARZ_OVB',
        'EPS_VRBB',
        'EPS_AAB',
        'EPS_BKS',
        'EPS_BKB',
        'EPS_VLB',
        'EPS_CBGG',
        'EPS_DB',
        'EPS_NOELB',
        'EPS_HBL',
        'EPS_MFB',
        'EPS_SPDBW',
        'EPS_SPDBA',
        'EPS_VKB',
    ];

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;

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
        $bankGroup = $dataBag->get('epsBankGroup');

        if (!\in_array($bankGroup, static::VALID_EPS_BANK_GROUPS, true)) {
            throw new PayoneRequestException('No valid EPS bank group');
        }
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
