<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInstallment;

use DateTime;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PayolutionPreCheckRequest
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

        $parameters = [
            'request'                   => 'genericpayment',
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Installment',
            'clearingtype'              => 'fnc',
            'financingtype'             => 'PYS',
            'amount'                    => (int) ($cart->getPrice()->getTotalPrice() * (10 ** $currency->getDecimalPrecision())),
            'currency'                  => $currency->getIsoCode(),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return array_filter($parameters);
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
