<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Repository;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;

class EntityRepositoryMock implements EntityRepositoryInterface
{
    /** @var null|Entity */
    private $entity;

    public function __construct(?Entity $entity)
    {
        $this->entity = $entity;
    }

    public function getDefinition(): EntityDefinition
    {
        return new EntityDefinitionMock();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(0, new EntityCollection(array_filter([$this->entity])), null, $criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []);
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }
}
