<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler as AbstractShopwarePaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractPaymentHandler extends AbstractShopwarePaymentHandler implements PaymentHandlerInterface
{
    protected readonly PaymentHandlerPayExecutorInterface $payExecutor;

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        Struct|null $validateStruct,
    ): RedirectResponse|null {
        $devcieFingerprintService = null;

        if ($this instanceof DeviceFingerprintAwareInterface) {
            $devcieFingerprintService = $this->getDeviceFingerprintService();
        }

        return $this->payExecutor->pay(
            $this,
            $request,
            $transaction,
            $context,
            $validateStruct,
            $devcieFingerprintService,
        );
    }
}
