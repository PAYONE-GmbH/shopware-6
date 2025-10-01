<?php

declare(strict_types=1);

namespace PayonePayment\DependencyInjection\Compiler;

use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\RequestParameter\RequestParameterPayEnricherRegistry;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

trait MergeRequestParameterEnricherCompilerPassTrait
{
    /**
     * @param class-string<Collection> $registryServiceId
     */
    public function merge(
        ContainerBuilder $container,
        string $serviceId,
        string $customTag,
        string $registryServiceId = RequestParameterPayEnricherRegistry::class,
        string $generalTag = 'payone.request_enricher.pay.general',
    ): void {
        $general = $this->collectTaggedServices($container, $generalTag, true);
        $custom  = $this->collectTaggedServices($container, $customTag, false);
        $merged  = \array_merge($custom, $general);

        \usort($merged, static function ($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return $a['is_general'] ? -1 : 1;
            }

            return $b['priority'] <=> $a['priority'];
        });

        $references = \array_map(static fn ($service) => new Reference($service['id']), $merged);

        $container->register($serviceId, RequestParameterEnricherChain::class)
            ->setArguments([ $references ])
            ->setPublic(false)
            ->setLazy(true)
        ;

        $definition = $container->getDefinition($registryServiceId);

        $definition->addMethodCall('set', [ $serviceId, new Reference($serviceId) ]);
    }

    private function collectTaggedServices(ContainerBuilder $container, string $tag, bool $isGeneral): array
    {
        $result = [];

        foreach ($container->findTaggedServiceIds($tag) as $id => $tags) {
            foreach ($tags as $t) {
                $priority = $t['priority'] ?? 0;

                $result[] = [
                    'id'         => $id,
                    'priority'   => $priority,
                    'is_general' => $isGeneral,
                ];
            }
        }

        return $result;
    }
}
