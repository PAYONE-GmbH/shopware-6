<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;

class PayoneKlarnaInstalmentPaymentHandler extends AbstractKlarnaPaymentHandler implements AsynchronousPaymentHandlerInterface
{
}
