<?php

declare(strict_types=1);

namespace PayonePayment\ConfigReader;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ConfigReader implements ConfigReaderInterface
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function read(string $salesChannelId = '', string $paymentMethodId = '', string $key = '')
    {
        $criteria = new Criteria();

        if (!empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter('payone_payment_config.sales_channel_id', $salesChannelId));
        }
        if (!empty($paymentMethodId)) {
            $criteria->addFilter(new EqualsFilter('payone_payment_config.payment_method_id', $paymentMethodId));
        }
        if (!empty($key)) {
            $criteria->addFilter(new EqualsFilter('payone_payment_config.key', $key));
        }

        $context = Context::createDefaultContext();

        return $this->repository->search($criteria, $context);
    }
}