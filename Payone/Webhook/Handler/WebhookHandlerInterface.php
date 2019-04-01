<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use Symfony\Component\HttpFoundation\Response;

interface WebhookHandlerInterface
{
    public function processAsync(array $data): Response;

    public function supports(array $data): bool;
}
