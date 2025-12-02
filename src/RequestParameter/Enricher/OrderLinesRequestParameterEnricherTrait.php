<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;

/**
 * @template T of PaymentRequestDto
 */
trait OrderLinesRequestParameterEnricherTrait
{
    protected readonly LineItemHydratorInterface $lineItemHydrator;

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        $paymentTransaction = $arguments->paymentTransaction;
        $currency           = $paymentTransaction->order->getCurrency();

        if (null === $currency || null === $paymentTransaction->order->getLineItems()) {
            return [];
        }

        $orderLines = $arguments->requestData->all('orderLines');

        if ([] !== $orderLines || !$this->isAuthorizeAction($arguments)) {
            return [];
        }

        return $this->lineItemHydrator->mapOrderLines(
            $currency,
            $paymentTransaction->order,
            $arguments->salesChannelContext->getContext(),
        );
    }

    private function isAuthorizeAction(PaymentRequestDto $arguments): bool
    {
        return
            RequestActionEnum::AUTHORIZE->value === $arguments->action
            || RequestActionEnum::PREAUTHORIZE->value === $arguments->action
        ;
    }
}
