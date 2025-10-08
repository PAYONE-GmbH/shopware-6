<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

use Shopware\Core\Framework\Struct\Collection;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @extends Collection<Reference|RequestParameterEnricherChain>
 */
class RequestParameterTestEnricherRegistry extends Collection
{
    #[\Override]
    protected function getExpectedClass(): string|null
    {
        return RequestParameterEnricherChain::class;
    }
}
