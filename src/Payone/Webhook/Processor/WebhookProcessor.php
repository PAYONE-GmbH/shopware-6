<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use IteratorAggregate;
use LogicException;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;

class WebhookProcessor implements WebhookProcessorInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var WebhookHandlerInterface[] */
    private $handlers;

    public function __construct(ConfigReaderInterface $configReader, IteratorAggregate $handlers)
    {
        $this->configReader = $configReader;
        $this->handlers     = iterator_to_array($handlers);
    }

    public function process(SalesChannelContext $salesChannelContext, array $data): Response
    {
        $config     = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $storedKeys = [
            hash('md5', $config->get('portalKey')),
        ];

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $prefix) {
            $storedKeys[] = hash('md5', $config->get(sprintf('%sPortalKey', $prefix)));
        }

        if (!isset($data['key']) || !in_array($data['key'], $storedKeys)) {
            return new Response(WebhookHandlerInterface::RESPONSE_TSNOTOK);
        }

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($salesChannelContext, $data)) {
                continue;
            }

            return $handler->process($salesChannelContext, $data);
        }

        throw new LogicException('Unable to identify a matching webhook handler');
    }
}
