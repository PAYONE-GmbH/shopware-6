<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Repository;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EntityDefinitionMock extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'entity';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([]);
    }
}
