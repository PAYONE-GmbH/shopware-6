<?php

declare(strict_types=1);

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Processor;

use PayonePayment\Bundle\PayoneBundle\Webhook\Factory\WebhookHandlerFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class WebhookProcessor implements WebhookProcessorInterface
{
    /** @var WebhookHandlerFactoryInterface */
    private $handlerFactory;

    public function __construct(WebhookHandlerFactoryInterface $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
    }

    public function process(array $data): Response
    {
        return $this->handlerFactory->getHandler($data)->processAsync($data);
    }
}
