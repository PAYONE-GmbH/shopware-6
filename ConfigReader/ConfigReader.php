<?php

declare(strict_types=1);

namespace PayonePayment\ConfigReader;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigCollection;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ConfigReader implements ConfigReaderInterface
{
    /** @var RepositoryInterface */
    private $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function read(string $salesChannelId = '', string $key = '', bool $fallback = true): PayonePaymentConfigCollection
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting(
            'payone_payment_config.salesChannelId',
            FieldSorting::ASCENDING
        ));
        $criteria->addSorting(new FieldSorting(
            'payone_payment_config.key',
            FieldSorting::ASCENDING
        ));

        if (!empty($salesChannelId)) {
            $null    = new EqualsFilter('payone_payment_config.salesChannelId', null);
            $channel = new EqualsFilter('payone_payment_config.salesChannelId', $salesChannelId);

            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                $null,
                $channel,
            ]));
        }

        if (!empty($key)) {
            $criteria->addFilter(new EqualsFilter(
                'payone_payment_config.key',
                $key
            ));
        }

        $context = Context::createDefaultContext();

        /** @var PayonePaymentConfigEntity[] $configElements */
        $configElements = $this->repository->search($criteria, $context);

        $collection = new PayonePaymentConfigCollection();
        foreach ($configElements as $element) {
            if ($fallback) {
                $collection->set($element->getKey(), $element);
            } else {
                $collection->add($element);
            }
        }

        return $collection;
    }
}
