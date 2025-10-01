<?php

declare(strict_types=1);

namespace PayonePayment\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface MergeRequestParameterEnricherCompilerPassInterface
{
    public function merge(
        ContainerBuilder $container,
        string $serviceId,
        string $customTag,
        string $generalTag = 'payone.request_enricher.pay.general',
    ): void;
}
