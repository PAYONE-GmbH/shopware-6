<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paysafe;

use DateTime;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaysafeInvoicingAuthorizeRequest
{
    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        Context $context
    ): array {
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context);

        $request = [
            'request'      => 'authorization',
            'clearingtype' => 'fnc',
            'financingtype' => 'PYV',
            'amount'       => (int) ($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision())),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $transaction->getOrder()->getOrderNumber(),
        ];

        if ($this->hasBirthdayParameters($dataBag)) {
            $birthday = (new DateTime())->setDate(
                (int) $dataBag->get('paysafeBirthdayYear'),
                (int) $dataBag->get('paysafeBirthdayMonth'),
                (int) $dataBag->get('paysafeBirthdayDay')
            );

            $request['birthday'] =  $birthday->format('Ymd');
        }

        return array_filter($request);
    }

    private function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    private function hasBirthdayParameters(RequestDataBag $dataBag): bool
    {
        if (empty($dataBag->get('paysafeBirthdayYear'))) {
            return false;
        }

        if (empty($dataBag->get('paysafeBirthdayMonth'))) {
            return false;
        }

        if (empty($dataBag->get('paysafeBirthdayDay'))) {
            return false;
        }

        return true;
    }
}
