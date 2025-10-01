<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Installment;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\Service\ProfileService;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\Enricher\ApplyBirthdayParameterTrait;
use PayonePayment\RequestParameter\Enricher\ApplyPhoneParameterTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ApplyBirthdayParameterTrait;
    use ApplyPhoneParameterTrait;

    public function __construct(
        protected OrderLoaderService $orderLoaderService,
        protected ProfileService $profileService,
        protected AbstractDeviceFingerprintService $deviceFingerprintService,
        protected RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $context             = $arguments->salesChannelContext->getContext();
        $paymentTransaction  = $arguments->paymentTransaction;

        $order    = $this->orderLoaderService->getOrderById($paymentTransaction->order->getId(), $context, true);
        $currency = $this->orderLoaderService->getOrderCurrency($order, $context);

        /** @noinspection NullPointerExceptionInspection */
        $profile = $this->profileService->getProfileByOrder($order, InstallmentPaymentHandler::class, true);

        $installmentAmount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            (float) $arguments->requestData->get('ratepayInstallmentAmount'),
            $currency,
        );

        $lastInstallmentAmount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            (float) $arguments->requestData->get('ratepayLastInstallmentAmount'),
            $currency,
        );

        $interestRate = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            (float) $arguments->requestData->get('ratepayInterestRate'),
            $currency,
        );

        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            (float) $arguments->requestData->get('ratepayTotalAmount'),
            $currency,
        );

        $installmentNumber = (int) $arguments->requestData->get('ratepayInstallmentNumber');

        /** @noinspection NullPointerExceptionInspection */
        $parameters = [
            'request'                                    => $requestActionEnum->value,
            'clearingtype'                               => PayoneClearingEnum::FINANCING->value,
            'financingtype'                              => PayoneFinancingEnum::RPS->value,
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[installment_amount]'            => $installmentAmount,
            'add_paydata[installment_number]'            => $installmentNumber,
            'add_paydata[last_installment_amount]'       => $lastInstallmentAmount,
            'add_paydata[interest_rate]'                 => $interestRate,
            'add_paydata[amount]'                        => $amount,
            'add_paydata[shop_id]'                       => $profile->getShopId(),
            'add_paydata[device_token]'                  => $this->deviceFingerprintService->getDeviceIdentToken(
                $arguments->salesChannelContext,
            ),
        ];

        if ($arguments->requestData->get('ratepayIban')) {
            $parameters['iban']                       = $arguments->requestData->get('ratepayIban');
            $parameters['add_paydata[debit_paytype]'] = 'DIRECT-DEBIT';
        } else {
            $parameters['add_paydata[debit_paytype]'] = 'BANK-TRANSFER';
        }

        /** @noinspection NullPointerExceptionInspection */
        $this->applyPhoneParameter($order, $parameters, $arguments->requestData, $context);

        /** @noinspection NullPointerExceptionInspection */
        $this->applyBirthdayParameter($order, $parameters, $arguments->requestData, $context);

        return $parameters;
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
