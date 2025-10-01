<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Installment;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum as GeneralRequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Provider\Ratepay\RequestParameter\CalculateRequestDto;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;

/**
 * @implements RequestParameterEnricherInterface<CalculateRequestDto>
 */
readonly class CalculationRequestParameterEnricher implements RequestParameterEnricherInterface
{
    final public const INSTALLMENT_TYPE_RATE = 'rate';

    final public const INSTALLMENT_TYPE_TIME = 'time';

    public function __construct(
        private OrderLoaderService $orderLoaderService,
        private RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $dataBag         = $arguments->requestData;
        $cart            = $arguments->cart;
        $profile         = $arguments->profile;
        $installmentType = $dataBag->get('ratepayInstallmentType');

        $currency = $this->orderLoaderService->getOrderCurrency(null, $arguments->salesChannelContext->getContext());

        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $cart->getPrice()->getTotalPrice(),
            $currency,
        );

        $parameters = [
            'request'                                    => GeneralRequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'                        => 'calculation',
            'clearingtype'                               => PayoneClearingEnum::FINANCING->value,
            'financingtype'                              => PayoneFinancingEnum::RPS->value,
            'amount'                                     => $amount,
            'currency'                                   => $currency->getIsoCode(),
            'add_paydata[shop_id]'                       => $profile->getShopId(),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
        ];

        if (self::INSTALLMENT_TYPE_RATE === $installmentType) {
            $rate = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
                (float) $dataBag->get('ratepayInstallmentValue'),
                $currency,
            );

            $parameters['add_paydata[calculation_type]'] = 'calculation-by-rate';
            $parameters['add_paydata[rate]']             = $rate;
        } elseif (self::INSTALLMENT_TYPE_TIME === $installmentType) {
            $parameters['add_paydata[calculation_type]'] = 'calculation-by-time';
            $parameters['add_paydata[month]']            = $dataBag->get('ratepayInstallmentValue');
        } else {
            throw new \RuntimeException('invalid installment type');
        }

        return $parameters;
    }
}
