<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class RequestBuilderServiceAccessor
{
    public function __construct(
        public readonly EntityRepository $customerRepository,
        public readonly EntityRepository $orderAddressRepository,
        public readonly EntityRepository $customerAddressRepository,
        public readonly EntityRepository $currencyRepository,
        public readonly CurrencyPrecisionInterface $currencyPrecision,
        public readonly LineItemHydratorInterface $lineItemHydrator
    ) {
    }
}
