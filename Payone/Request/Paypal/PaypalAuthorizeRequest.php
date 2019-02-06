<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\RequestInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Router;

class PaypalAuthorizeRequest implements RequestInterface
{
    /** @var Router */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getParentRequest(): string
    {
        return CustomerRequest::class;
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        $cancelUrl = $this->router->generate(
            'payone_payment_cancel',
            [
                'transaction' => $transaction->getTransactionId(),
            ],
            UrlGenerator::ABSOLUTE_URL
        );

        $errorUrl = $this->router->generate(
            'payone_payment_error',
            [
                'transaction' => $transaction->getTransactionId(),
            ],
            UrlGenerator::ABSOLUTE_URL
        );

        return [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) ($transaction->getAmount()->getTotalPrice() * 100),
            'currency'     => 'EUR',
            'reference'    => $transaction->getOrder()->getAutoIncrement(), // TODO: replace with ordernumber when available
            'successurl'   => $transaction->getReturnUrl(),
            'errorurl'     => $errorUrl,
            'backurl'      => $cancelUrl,
        ];
    }
}
