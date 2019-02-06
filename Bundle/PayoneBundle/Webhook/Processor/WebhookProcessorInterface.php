<?php

declare(strict_types=1);

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Processor;

use Symfony\Component\HttpFoundation\Response;

interface WebhookProcessorInterface
{
    /**
     * Processes the provided webhook data
     *
     * @param array  $data
     *
     * @return Response
     */
    public function process(array $data): Response;
}
