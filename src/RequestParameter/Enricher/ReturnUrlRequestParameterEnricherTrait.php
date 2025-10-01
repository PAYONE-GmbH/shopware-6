<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;

/**
 * @template T of PaymentRequestDto
 */
trait ReturnUrlRequestParameterEnricherTrait
{
    protected readonly RedirectHandler $redirectHandler;

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        $paymentTransaction = $arguments->paymentTransaction;

        return [
            'successurl' => $this->redirectHandler->encode($paymentTransaction->returnUrl . '&state=success'),
            'errorurl'   => $this->redirectHandler->encode($paymentTransaction->returnUrl . '&state=error'),
            'backurl'    => $this->redirectHandler->encode($paymentTransaction->returnUrl . '&state=cancel'),
        ];
    }
}
