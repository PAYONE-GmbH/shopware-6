<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.profile.ratepay.debit',
            'payone.request_enricher.profile.ratepay.debit',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.ratepay.debit',
            'payone.request_enricher.pay.ratepay.debit',
        );

        $this->merge($container,
            'payone.request_enricher_chain.calculate.ratepay.installment',
            'payone.request_enricher.calculate.ratepay.installment',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.profile.ratepay.installment',
            'payone.request_enricher.profile.ratepay.installment',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.ratepay.installment',
            'payone.request_enricher.pay.ratepay.installment',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.ratepay.invoice',
            'payone.request_enricher.pay.ratepay.invoice',
        );

        $this->merge($container,
            'payone.request_enricher_chain.profile.ratepay.invoice',
            'payone.request_enricher.profile.ratepay.invoice',
            generalTag: 'payone.request_enricher.system.general',
        );
    }
}
