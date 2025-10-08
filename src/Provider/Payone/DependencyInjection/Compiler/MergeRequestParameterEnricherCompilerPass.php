<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.check.payone.credit_card',
            'payone.request_enricher.check.payone.credit_card',
            generalTag: 'payone.request_enricher.check.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.credit_card',
            'payone.request_enricher.pay.payone.credit_card',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.debit',
            'payone.request_enricher.pay.payone.debit',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.open_invoice',
            'payone.request_enricher.pay.payone.open_invoice',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.prepayment',
            'payone.request_enricher.pay.payone.prepayment',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.secured_direct_debit',
            'payone.request_enricher.pay.payone.secured_direct_debit',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.secured_installment',
            'payone.request_enricher.pay.payone.secured_installment',
        );

        $this->merge($container,
            'payone.request_enricher_chain.option.payone.secured_installment.load',
            'payone.request_enricher.option.payone.secured_installment.load',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.secured_invoice',
            'payone.request_enricher.pay.payone.secured_invoice',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.payone.secure_invoice',
            'payone.request_enricher.pay.payone.secure_invoice',
        );
    }
}
