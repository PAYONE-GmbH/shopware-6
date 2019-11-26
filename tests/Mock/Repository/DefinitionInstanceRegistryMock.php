<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Repository;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefinitionInstanceRegistryMock extends DefinitionInstanceRegistry
{
    public function __construct(array $elements, ContainerInterface $container)
    {
        parent::__construct($container, $elements, []);
    }
}
