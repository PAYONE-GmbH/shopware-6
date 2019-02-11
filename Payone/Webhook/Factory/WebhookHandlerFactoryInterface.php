<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Factory;

use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;

interface WebhookHandlerFactoryInterface
{
    /**
     * Returns a matching WebhookHandler for the provided data.
     *
     * @param array $data
     *
     * @return WebhookHandlerInterface
     */
    public function getHandler(array $data): WebhookHandlerInterface;
}
