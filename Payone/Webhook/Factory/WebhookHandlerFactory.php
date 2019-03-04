<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Factory;

use LogicException;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use Psr\Container\ContainerInterface;

class WebhookHandlerFactory implements WebhookHandlerFactoryInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getHandler(array $data): WebhookHandlerInterface
    {
        $handler = $this->getHandlerName($data);

        if ($handler === null) {
            throw new LogicException('Unable to identify a matching webhook handler');
        }

        if ($this->container->has($handler)) {
            return $this->container->get($handler);
        }

        throw new LogicException(sprintf('There is no webhook handler registered for %s', $handler));
    }

    private function getHandlerName(array $data)
    {
        //TODO: Find a better way to identify the correct webhook handler.
        if (array_key_exists('txaction', $data)) {
            return TransactionStatusWebhookHandler::class;
        }

        return null;
    }
}
