<?php

declare(strict_types=1);

namespace PayonePayment\ConfigWriter;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigCollection;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ConfigWriter implements ConfigWriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function write(string $key, string $value, string $salesChannelId = ''): void
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'payone_payment_config.key',
            $key
        ));

        if (empty($salesChannelId)) {
            $criteria->addFilter(new EqualsFilter(
                'payone_payment_config.salesChannelId',
                null
            ));
        } else {
            $criteria->addFilter(new EqualsFilter(
                'payone_payment_config.salesChannelId',
                $salesChannelId
            ));
        }

        /** @var PayonePaymentConfigEntity|null $existingEntry */
        $existingEntry = $this->repository->search($criteria, $context)->first();

        if (null === $existingEntry) {
            $data = [
                'key' => $key,
                'value' => $value,
            ];

            $this->repository->create([$data], $context);
        } else {
            $data = [
                'id' => $existingEntry->getId(),
                'key' => $key,
                'value' => $value,
            ];

            $this->repository->update([$data], $context);
        }
    }
}