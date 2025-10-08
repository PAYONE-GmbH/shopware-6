<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.pay.payolution.debit',
            'payone.request_enricher.pay.payolution.debit',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payolution.installment',
            'payone.request_enricher.pay.payolution.installment',
        );

        $this->merge($container,
            'payone.request_enricher_chain.calculate.payolution.installment',
            'payone.request_enricher.calculate.payolution.installment',
            generalTag: 'payone.request_enricher.calculate.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.check.payolution.installment',
            'payone.request_enricher.check.payolution.installment',
            generalTag: 'payone.request_enricher.check.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payolution.invoice',
            'payone.request_enricher.pay.payolution.invoice',
        );

        $this->merge($container,
            'payone.request_enricher_chain.check.payolution.invoice',
            'payone.request_enricher.check.payolution.invoice',
            generalTag: 'payone.request_enricher.check.general',
        );
    }
}
