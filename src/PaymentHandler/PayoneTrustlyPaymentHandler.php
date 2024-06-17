<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayoneTrustlyPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($dataBag, $salesChannelContext);

        $definitions['iban'] = [new NotBlank(), new Iban()];

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if ($payoneTransActionData['authorizationType'] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    public static function isRefundable(array $transactionData): bool
    {
        if ((float) $transactionData['receivable'] !== 0.0 && strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE) {
            return true;
        }

        return strtolower((string) $transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
