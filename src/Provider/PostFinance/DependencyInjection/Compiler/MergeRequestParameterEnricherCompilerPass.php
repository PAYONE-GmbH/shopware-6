<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PostFinance\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.pay.post_finance.card',
            'payone.request_enricher.pay.post_finance.card',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.post_finance.wallet',
            'payone.request_enricher.pay.post_finance.wallet',
        );
    }
}
