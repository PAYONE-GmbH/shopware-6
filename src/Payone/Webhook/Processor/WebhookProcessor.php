<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use Exception;
use IteratorAggregate;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;

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
        $storedKeys = [hash('md5', $config->getString('portalKey'))];

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $prefix) {
            $key = $config->getString(sprintf('%sPortalKey', $prefix));

            if (empty($key)) {
                continue;
            }

            $storedKeys[] = hash('md5', $key);
        }

        if (!isset($data['key']) || !in_array($data['key'], $storedKeys)) {
            $this->logger->error('Received webhook without known portal key', $data);

            return new Response(WebhookHandlerInterface::RESPONSE_TSNOTOK);
        }

        $response = WebhookHandlerInterface::RESPONSE_TSOK;

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($salesChannelContext, $data)) {
                $this->logger->debug(sprintf('Skipping webhook handler %s', get_class($handler)), $data);

                continue;
            }

            try {
                $handler->process($salesChannelContext, $data);

                $this->logger->info(sprintf('Processed webhook handler %s', get_class($handler)), $data);
            } catch (Exception $exception) {
                $this->logger->error(sprintf('Error during processing of webhook handler %s', get_class($handler)), [
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'data'    => $data,
                ]);

                $response = WebhookHandlerInterface::RESPONSE_TSNOTOK;

                break;
            }
        }

        return new Response($response);
    }
}
