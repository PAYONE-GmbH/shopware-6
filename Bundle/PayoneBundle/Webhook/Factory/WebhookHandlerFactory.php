<?php

declare(strict_types=1);

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Factory;

use LogicException;
use PayonePayment\Bundle\PayoneBundle\Webhook\Handler\WebhookHandlerInterface;
use PayonePayment\Bundle\PayoneBundle\Webhook\Handler\TransactionStatusWebhookHandler;
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
        //TODO: Find a better way to identify the correct webhook handler.. this is crap.
        if (array_key_exists('txaction', $data)) {
            return TransactionStatusWebhookHandler::class;
        }

        return null;
    }
}
