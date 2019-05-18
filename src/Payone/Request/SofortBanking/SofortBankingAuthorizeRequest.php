<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\SofortBanking;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class SofortBankingAuthorizeRequest
{
    /** @var RedirectHandler */
    private $redirectHandler;

    public function __construct(RedirectHandler $redirectHandler)
    {
        $this->redirectHandler = $redirectHandler;
    }

    public function getRequestParameters(
        PaymentTransactionStruct $transaction,
        Context $context
    ): array {
        if (empty($transaction->getReturnUrl())) {
            throw new InvalidOrderException($transaction->getOrder()->getId());
        }

        return [
            'request'                => 'authorization',
            'clearingtype'           => 'sb',
            'onlinebanktransfertype' => 'PNT',
            'bankcountry'            => 'DE', // DE, AT, CH, NL
            'amount'                 => (int) ($transaction->getOrder()->getAmountTotal() * 100),
            'currency'               => $transaction->getOrder()->getCurrency()->getIsoCode(),
            'reference'              => $transaction->getOrder()->getOrderNumber(),
            'successurl'             => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=success'),
            'errorurl'               => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=error'),
            'backurl'                => $this->redirectHandler->encode($transaction->getReturnUrl() . '&state=cancel'),
        ];
    }
}
