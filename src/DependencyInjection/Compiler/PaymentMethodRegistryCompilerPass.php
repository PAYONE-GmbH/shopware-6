<?php

declare(strict_types=1);

namespace PayonePayment\DependencyInjection\Compiler;

use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Path;

class PaymentMethodRegistryCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $definition     = $container->getDefinition(PaymentMethodRegistry::class);
        $pathToConfig   = Path::join(__DIR__, '../../Resources/config/payment_methods.php');
        $paymentMethods = require $pathToConfig;

        /** @var class-string<PaymentMethodInterface> $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            if (!$container->has($paymentMethod)) {
                $container->register($paymentMethod, $paymentMethod);
            }

            $definition->addMethodCall('set', [ $paymentMethod::getTechnicalName(), new Reference($paymentMethod) ]);
        }
    }
}
