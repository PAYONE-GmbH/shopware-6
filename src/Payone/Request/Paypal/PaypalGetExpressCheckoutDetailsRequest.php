<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaypalGetExpressCheckoutDetailsRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(Cart $cart, Context $context, string $workOrderId): array
    {
        $currency = $this->getOrderCurrency($context);

        return [
            'request'             => 'genericpayment',
            'clearingtype'        => 'wlt',
            'wallettype'          => 'PPE',
            'add_paydata[action]' => 'getexpresscheckoutdetails',
            'amount'              => (int) ($cart->getPrice()->getTotalPrice() * (10 ** $currency->getDecimalPrecision())),
            'currency'            => $currency->getIsoCode(),
            'workorderid'         => $workOrderId,
        ];
    }

    private function getOrderCurrency(Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$context->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }
}
