<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PaysafeInstallment;

use DateTime;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaysafePreCheckRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        Cart $cart,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $currency = $this->getCurrency($context->getCurrencyId(), $context);

        $request = [
            'request'       => 'genericpayment',
            'add_paydata[action]' => 'pre_check',
            'clearingtype'  => 'fnc',
            'financingtype' => 'PYV',
            'amount'        => (int) ($cart->getPrice()->getTotalPrice() * (10 ** $currency->getDecimalPrecision())),
            'currency'      => $currency->getIsoCode(),
        ];

        if (!empty($dataBag->get('paysafeInvoicingBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('paysafeInvoicingBirthday'));

            if (!empty($birthday)) {
                $request['birthday'] = $birthday->format('Ymd');
            }
        }

        return array_filter($request);
    }

    private function getCurrency(string $id, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$id]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing currency entity');
        }

        return $currency;
    }
}
