<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use IteratorAggregate;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookProcessor implements WebhookProcessorInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var WebhookHandlerInterface[] */
    private $handlers;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigReaderInterface $configReader,
        IteratorAggregate $handlers,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->handlers     = iterator_to_array($handlers);
        $this->logger       = $logger;
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

        $response = WebhookHandlerInterface::RESPONSE_TSOK;

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($salesChannelContext, $data)) {
                continue;
            }

            try {
                $handler->process($salesChannelContext, $data);
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage(), [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]);

                $response = WebhookHandlerInterface::RESPONSE_TSNOTOK;
            }
        }

        return new Response($response);
    }
}
