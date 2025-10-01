<?php

declare(strict_types=1);

namespace PayonePayment\DependencyInjection\Compiler;

use PayonePayment\RequestParameter\RequestParameterTestEnricherRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestEnricherTestCompilerPass implements CompilerPassInterface
{
    use MergeRequestParameterEnricherCompilerPassTrait;

    public function process(ContainerBuilder $container): void
    {
        $this->merge($container,
            'payone.request_enricher_chain.test',
            'payone.request_enricher.test',
            RequestParameterTestEnricherRegistry::class,
            'payone.request_enricher.test.general',
        );
    }
}
