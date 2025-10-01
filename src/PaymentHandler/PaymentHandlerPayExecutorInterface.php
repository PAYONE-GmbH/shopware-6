<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

interface PaymentHandlerPayExecutorInterface
{
    public function pay(
        PaymentHandlerInterface $paymentHandler,
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        Struct|null $validateStruct,
        AbstractDeviceFingerprintService|null $deviceFingerprintService,
    ): RedirectResponse|null;
}
