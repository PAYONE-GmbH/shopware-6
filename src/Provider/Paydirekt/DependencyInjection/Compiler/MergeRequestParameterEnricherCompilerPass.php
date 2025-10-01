<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Paydirekt\DependencyInjection\Compiler;

use PayonePayment\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPassInterface;
use PayonePayment\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPassTrait;
use PayonePayment\RequestParameter\RequestParameterTestEnricherRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MergeRequestParameterEnricherCompilerPass implements
    CompilerPassInterface,
    MergeRequestParameterEnricherCompilerPassInterface
{
    use MergeRequestParameterEnricherCompilerPassTrait;

    public function process(ContainerBuilder $container): void
    {
        $this->merge($container,
            'payone.request_enricher_chain.pay.paydirekt.standard',
            'payone.request_enricher.pay.paydirekt.standard',
        );
    }
}
