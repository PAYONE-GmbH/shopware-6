<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\DependencyInjection\Compiler;

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
            'payone.request_enricher_chain.session.pay_pal.express.create',
            'payone.request_enricher.session.pay_pal.express.create',
            generalTag: 'payone.request_enricher.session.general.create',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.pay_pal.express.get',
            'payone.request_enricher.session.pay_pal.express.get',
            generalTag: 'payone.request_enricher.session.general.get',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.pay_pal.express.update',
            'payone.request_enricher.session.pay_pal.express.update',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.pay_pal.express',
            'payone.request_enricher.pay.pay_pal.express',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.pay_pal.standard',
            'payone.request_enricher.pay.pay_pal.standard',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.pay_pal_v2.express.create',
            'payone.request_enricher.session.pay_pal_v2.express.create',
            generalTag: 'payone.request_enricher.session.general.create',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.pay_pal_v2.express.get',
            'payone.request_enricher.session.pay_pal_v2.express.get',
            generalTag: 'payone.request_enricher.session.general.get',
        );

        $this->merge($container,
            'payone.request_enricher_chain.session.pay_pal_v2.express.update',
            'payone.request_enricher.session.pay_pal_v2.express.update',
            generalTag: 'payone.request_enricher.system.general',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.pay_pal_v2.express',
            'payone.request_enricher.pay.pay_pal_v2.express',
        );

        $this->merge($container,
            'payone.request_enricher_chain.pay.pay_pal_v2.standard',
            'payone.request_enricher.pay.pay_pal_v2.standard',
        );
    }
}
