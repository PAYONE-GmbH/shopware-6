<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Debit;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
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
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $context            = $arguments->salesChannelContext->getContext();
        $paymentTransaction = $arguments->paymentTransaction;

        $order = $this->orderLoaderService->getOrderById($paymentTransaction->order->getId(), $context, true);

        /** @noinspection NullPointerExceptionInspection */
        $profile = $this->profileService->getProfileByOrder($order, DebitPaymentHandler::class, true);

        /** @noinspection NullPointerExceptionInspection */
        $parameters = [
            'request'                                    => $requestActionEnum->value,
            'clearingtype'                               => PayoneClearingEnum::FINANCING->value,
            'financingtype'                              => PayoneFinancingEnum::RPD->value,
            'iban'                                       => $arguments->requestData->get('ratepayIban'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[shop_id]'                       => $profile->getShopId(),
            'add_paydata[device_token]'                  => $this->deviceFingerprintService->getDeviceIdentToken(
                $arguments->salesChannelContext,
            ),
        ];

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
