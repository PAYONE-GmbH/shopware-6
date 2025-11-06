<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay\DependencyInjection\Compiler;

use PayonePayment\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPassInterface;
use PayonePayment\DependencyInjection\Compiler\MergeRequestParameterEnricherCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MergeRequestParameterEnricherCompilerPass implements
    CompilerPassInterface,
    MergeRequestParameterEnricherCompilerPassInterface
{
    use MergeRequestParameterEnricherCompilerPassTrait;

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $this->merge($container,
            'payone.request_enricher_chain.pay.google_pay.standard',
            'payone.request_enricher.pay.google_pay.standard',
        );
    }
}
