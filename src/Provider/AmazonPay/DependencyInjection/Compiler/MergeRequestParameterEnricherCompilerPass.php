<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.session.amazon.express.create',
            'payone.request_enricher.session.amazon.express.create',
            generalTag: 'payone.request_enricher.session.general.create',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.amazon.express.get',
            'payone.request_enricher.session.amazon.express.get',
            generalTag: 'payone.request_enricher.session.general.get',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.amazon.express.update',
            'payone.request_enricher.session.amazon.express.update',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.amazon_pay.express',
            'payone.request_enricher.pay.amazon_pay.express',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.amazon_pay.standard',
            'payone.request_enricher.pay.amazon_pay.standard',
        );
    }
}
