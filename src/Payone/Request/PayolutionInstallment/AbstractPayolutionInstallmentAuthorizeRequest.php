<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInstallment;

use DateTime;
use PayonePayment\Struct\PaymentTransaction;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPayolutionInstallmentAuthorizeRequest
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
        SalesChannelContext $context,
        string $referenceNumber
    ): array {
        $currency = $this->getOrderCurrency($transaction->getOrder(), $context->getContext());

        $parameters = [
            'clearingtype'                      => 'fnc',
            'financingtype'                     => 'PYS',
            'add_paydata[installment_duration]' => (int) $dataBag->get('payolutionInstallmentDuration'),
            'amount'                            => (int) round(($transaction->getOrder()->getAmountTotal() * (10 ** $currency->getDecimalPrecision()))),
            'currency'                          => $currency->getIsoCode(),
            'reference'                         => $referenceNumber,
            'iban'                              => $dataBag->get('payolutionIban'),
            'bic'                               => $dataBag->get('payolutionBic'),
            'bankaccountholder'                 => $dataBag->get('payolutionAccountOwner'),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        if (!empty($dataBag->get('workorder'))) {
            $parameters['workorderid'] = $dataBag->get('workorder');
        }

        return array_filter($parameters);
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
}
