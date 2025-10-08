<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Debit;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\Enricher\ApplyBirthdayParameterTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ApplyBirthdayParameterTrait;

    public function __construct(
        protected OrderLoaderService $orderLoaderService,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $parameters = [
            'request'       => $requestActionEnum->value,
            'clearingtype'  => PayoneClearingEnum::FINANCING->value,
            'financingtype' => 'PYD',
            'iban'          => $arguments->requestData->get('payolutionIban'),
            'bic'           => $arguments->requestData->get('payolutionBic'),
        ];

        $context = $arguments->salesChannelContext->getContext();
        $order   = $this->orderLoaderService->getOrderById(
            $arguments->paymentTransaction->order->getId(),
            $context,
            true,
        );

        /** @noinspection NullPointerExceptionInspection */
        $this->applyBirthdayParameter($order, $parameters, $arguments->requestData, $context);

        return $parameters;
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
