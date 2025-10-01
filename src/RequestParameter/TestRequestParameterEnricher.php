<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class TestRequestParameterEnricher
{
    public function enrich(
        string $paymentHandlerIdentifier,
        RequestParameterEnricherChain $enrichers
    ): RequestDataBag|null {
        /** @var TestRequestParameterEnricherInterface $enricher */
        foreach ($enrichers->getElements() as $enricher) {
            if (
                $enricher->isActive()
                && $enricher->getPaymentHandlerIdentifier() === $paymentHandlerIdentifier
            ) {
                return new RequestDataBag(
                    $enricher->getParameters(),
                );
            }
        }

        return null;
    }
}
