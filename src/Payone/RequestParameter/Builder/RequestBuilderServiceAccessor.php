<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Service\CurrencyPrecisionService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @deprecated use / inject services explicitly
 */
final readonly class RequestBuilderServiceAccessor
{
    public function __construct(
        public EntityRepository $customerRepository,
        public EntityRepository $orderAddressRepository,
        public EntityRepository $customerAddressRepository,
        public EntityRepository $currencyRepository,
        public CurrencyPrecisionService $currencyPrecision,
        public LineItemHydratorInterface $lineItemHydrator,
        public ConfigReader $configReader,
    ) {
    }
}
