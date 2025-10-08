<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Installment;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum as GeneralRequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class PreCheckRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private OrderLoaderService $orderLoaderService,
        private RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $dataBag             = $arguments->requestData;
        $salesChannelContext = $arguments->salesChannelContext;
        $currency            = $this->orderLoaderService->getOrderCurrency(null, $salesChannelContext->getContext());
        $cart                = $arguments->cart;

        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $cart->getPrice()->getTotalPrice(),
            $currency,
        );

        $parameters = [
            'request'                   => GeneralRequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Installment',
            'clearingtype'              => PayoneClearingEnum::FINANCING->value,
            'financingtype'             => 'PYS',
            'amount'                    => $amount,
            'currency'                  => $currency->getIsoCode(),
            'workorderid'               => $dataBag->get(RequestConstantsEnum::WORK_ORDER_ID->value, ''),
        ];

        if (!empty($dataBag->get(RequestConstantsEnum::BIRTHDAY->value))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get(RequestConstantsEnum::BIRTHDAY->value));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return $parameters;
    }
}
