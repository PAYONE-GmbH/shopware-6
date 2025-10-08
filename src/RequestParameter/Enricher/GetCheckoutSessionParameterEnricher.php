<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class GetCheckoutSessionParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private RequestBuilderServiceAccessor $serviceAccessor,
        private CartExtensionService $cartExtensionService,
        private TranslatorInterface $translator,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $cartExtension = $this->cartExtensionService->getCartExtensionForExpressCheckout(
            $arguments->cart,
            $arguments->paymentHandler->getPaymentMethodUuid(),
        );

        if (null === $cartExtension) {
            throw new \RuntimeException($this->translator->trans('PayonePayment.errorMessages.genericError'));
        }

        $salesChannelContaxt = $arguments->salesChannelContext;
        $currency            = $salesChannelContaxt->getCurrency();
        $amount              = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $arguments->cart->getPrice()->getTotalPrice(),
            $currency,
        );

        return [
            'request'     => RequestActionEnum::GENERIC_PAYMENT->value,
            'workorderid' => $cartExtension->getWorkOrderId(),
            'amount'      => $amount,
            'currency'    => $currency->getIsoCode(),
        ];
    }
}
