<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.session.klarna.create',
            'payone.request_enricher.session.klarna.create',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.klarna.direct_debit',
            'payone.request_enricher.pay.klarna.direct_debit',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.klarna.installment',
            'payone.request_enricher.pay.klarna.installment',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.klarna.invoice',
            'payone.request_enricher.pay.klarna.invoice',
        );
    }
}
