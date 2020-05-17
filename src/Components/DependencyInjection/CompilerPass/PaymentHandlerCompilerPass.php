<?php

declare(strict_types=1);

namespace PayonePayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentHandlerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('PayonePayment\Components\DependencyInjection\Factory\PaymentHandlerFactory')) {
            return;
        }

        $definition     = $container->getDefinition('PayonePayment\Components\DependencyInjection\Factory\PaymentHandlerFactory');
        $taggedServices = $container->findTaggedServiceIds('payone_payment.payment_handler');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addPaymentHandler',
                [
                    new Reference($id),
                ]
            );


        }
    }
}
