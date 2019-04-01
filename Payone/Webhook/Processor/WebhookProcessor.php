<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use LogicException;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

class WebhookProcessor implements WebhookProcessorInterface
{
    /** @var WebhookHandlerInterface[] */
    private $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function process(array $data): Response
    {
        foreach ($this->handlers as $handler) {
            if (!$handler->supports($data)) {
                continue;
            }

            return $handler->processAsync($data);
        }


        throw new LogicException('Unable to identify a matching webhook handler');
    }
}
