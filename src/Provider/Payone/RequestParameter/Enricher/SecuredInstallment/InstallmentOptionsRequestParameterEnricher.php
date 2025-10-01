<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\SecuredInstallment;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class InstallmentOptionsRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private RequestBuilderServiceAccessor $serviceAccessor,
        private OrderLoaderService $orderLoaderService,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $currency = $this->orderLoaderService->getOrderCurrency(null, $arguments->salesChannelContext->getContext());
        $cart     = $arguments->cart;
        $amount   = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $cart->getPrice()->getTotalPrice(),
            $currency,
        );

        return [
            'request'                       => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'           => 'installment_options',
            'add_paydata[businessRelation]' => 'b2c',
            'clearingtype'                  => PayoneClearingEnum::FINANCING->value,
            'financingtype'                 => PayoneFinancingEnum::PIN->value,
            'amount'                        => $amount,
            'currency'                      => $currency->getIsoCode(),
        ];
    }
}
