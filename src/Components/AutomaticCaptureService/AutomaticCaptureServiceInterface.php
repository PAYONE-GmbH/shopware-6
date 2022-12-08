<?php

declare(strict_types=1);

namespace PayonePayment\Components\AutomaticCaptureService;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface AutomaticCaptureServiceInterface
{
    public function captureIfPossible(PaymentTransaction $paymentTransaction, SalesChannelContext $salesChannelContext): void;
}
