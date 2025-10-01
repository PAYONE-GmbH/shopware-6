<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class WebhookProcessor implements WebhookProcessorInterface
{
    /**
     * @var WebhookHandlerInterface[]
     */
    private array $handlers;

    public function __construct(
        private ConfigReaderInterface $configReader,
        \IteratorAggregate $handlers,
        private LoggerInterface $logger,
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
        $this->handlers = iterator_to_array($handlers);
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): Response
    {
        $data = $request->request->all();

        $config     = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $storedKeys = [hash('md5', $config->getString('portalKey'))];

        /** @var PaymentMethodInterface $paymentMethod */
        foreach ($this->paymentMethodRegistry as $paymentMethod) {
            $prefix = $paymentMethod::getConfigurationPrefix();
            $key    = $config->getString(sprintf('%sPortalKey', $prefix));

            if (empty($key)) {
                continue;
            }

            $storedKeys[] = hash('md5', $key);
        }

        if (!isset($data['key']) || !\in_array($data['key'], $storedKeys, true)) {
            $this->logger->error('Received webhook without known portal key', $data);

            return new Response(WebhookHandlerInterface::RESPONSE_TSNOTOK);
        }

        $response = WebhookHandlerInterface::RESPONSE_TSOK;

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($salesChannelContext, $data)) {
                $this->logger->debug(sprintf('Skipping webhook handler %s', $handler::class), $data);

                continue;
            }

            try {
                $handler->process($salesChannelContext, $request);

                $this->logger->info(sprintf('Processed webhook handler %s', $handler::class), $data);
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Error during processing of webhook handler %s', $handler::class), [
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
