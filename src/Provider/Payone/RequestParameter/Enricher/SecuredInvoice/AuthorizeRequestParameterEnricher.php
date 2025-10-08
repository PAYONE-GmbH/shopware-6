<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\SecuredInvoice;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\Enricher\ApplyBirthdayParameterTrait;
use PayonePayment\RequestParameter\Enricher\ApplyPhoneParameterTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ApplyBirthdayParameterTrait;
    use ApplyPhoneParameterTrait;
    use ApplyB2bParametersTrait;

    public function __construct(
        protected RequestBuilderServiceAccessor $serviceAccessor,
        protected OrderLoaderService $orderLoaderService,
        protected AbstractDeviceFingerprintService $deviceFingerprintService,
        EntityRepository $orderAddressRepository,
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $dataBag             = $arguments->requestData;
        $salesChannelContext = $arguments->salesChannelContext;
        $context             = $salesChannelContext->getContext();
        $paymentTransaction  = $arguments->paymentTransaction;

        $order    = $this->orderLoaderService->getOrderById($paymentTransaction->order->getId(), $context, true);
        $currency = $this->orderLoaderService->getOrderCurrency($order, $context);

        /** @noinspection NullPointerExceptionInspection */
        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($order->getAmountTotal(), $currency);

        $parameters = [
            'request'                   => $requestActionEnum->value,
            'clearingtype'              => PayoneClearingEnum::FINANCING->value,
            'financingtype'             => PayoneFinancingEnum::PIV->value,
            'add_paydata[device_token]' => $this->deviceFingerprintService->getDeviceIdentToken($salesChannelContext),
            'amount'                    => $amount,
            'currency'                  => $currency->getIsoCode(),
        ];

        /** @noinspection NullPointerExceptionInspection */
        $this->applyPhoneParameter($order, $parameters, $dataBag, $context);

        /** @noinspection NullPointerExceptionInspection */
        $this->applyBirthdayParameter($order, $parameters, $dataBag, $context);

        /** @noinspection NullPointerExceptionInspection */
        $this->applyB2bParameters($order, $parameters);

        return $parameters;
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
