<?php

declare(strict_types=1);

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Handler;

use Symfony\Component\HttpFoundation\Response;

interface WebhookHandlerInterface
{
    public function processAsync(array $data): Response;
}
