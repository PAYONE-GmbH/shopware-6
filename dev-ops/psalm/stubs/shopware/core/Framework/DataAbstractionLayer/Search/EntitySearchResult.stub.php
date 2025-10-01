<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Search;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @final
 *
 * @extends EntityCollection<Entity>
 */
#[Package('framework')]
class EntitySearchResult extends EntityCollection
{
    /**
     * @return Entity|null
     */
    public function getAt(int $position);

    /**
     * @param iterable<Entity> $elements
     */
    protected function createNew(iterable $elements = []): static;
}
