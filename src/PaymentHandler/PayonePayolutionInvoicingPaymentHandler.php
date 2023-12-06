<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\PaymentMethod;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayonePayolutionInvoicingPaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        $definitions['payolutionConsent'] = [new NotBlank()];

        // if the customer has a company address, the birthday is not required
        if ($salesChannelContext->getCustomer()?->getDefaultBillingAddress()?->getCompany() === null) {
            $definitions['payolutionBirthday'] = [new NotBlank(), new Birthday(['value' => $this->getMinimumDate()])];
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if (!$configuration->get('payolutionInvoicingTransferCompanyData') && $this->customerHasCompanyAddress($salesChannelContext)) {
            $definitions['payonePaymentMethod'] = [new PaymentMethod(['value' => $salesChannelContext->getPaymentMethod()])];
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

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'workOrderId' => $dataBag->get('workorder'),
            'clearingReference' => $response['addpaydata']['clearing_reference'],
            'captureMode' => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            'financingType' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYV,
        ];
    }
}
