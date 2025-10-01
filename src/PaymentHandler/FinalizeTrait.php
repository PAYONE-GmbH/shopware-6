<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

trait FinalizeTrait
{
    protected readonly PaymentStateHandlerService $stateHandler;

    public function finalize(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
    ): void {
        $this->stateHandler->handleStateResponse($transaction, (string) $request->query->get('state'));
    }
}
