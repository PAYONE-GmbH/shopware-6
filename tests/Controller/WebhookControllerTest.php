<?php

declare(strict_types=1);

namespace PayonePayment\Test\Controller;

use PayonePayment\Controller\WebhookController;
use PayonePayment\Payone\Webhook\Processor\WebhookProcessor;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

class WebhookControllerTest extends TestCase
{
    public function testCreditcardAppointed(): void
    {
        $this->markTestIncomplete();

        return;

        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $response = $this->createWebhookController()->execute(
            new Request(),
            $salesChannelContext
        );
    }

    private function createWebhookController(): WebhookController
    {
        // TODO: Use mocks for dependencies of WebhookProcessor
        return new WebhookController(
            new WebhookProcessor()
        );
    }
}
