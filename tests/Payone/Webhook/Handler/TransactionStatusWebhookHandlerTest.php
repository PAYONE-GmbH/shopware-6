<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Webhook\Handler;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use PayonePayment\Test\Mock\EventDispatcherMock;
use PayonePayment\Test\Mock\Repository\DefinitionInstanceRegistryMock;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PayonePayment\Test\Mock\Setting\Service\SystemConfigServiceMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class TransactionStatusWebhookHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    public function testCreditcardAppointed(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $this->createHandler()->process($salesChannelContext, []);
    }

    private function createHandler(): WebhookHandlerInterface
    {
        return new TransactionStatusWebhookHandler(
            new TransactionStatusService(
                new EntityRepositoryMock(),
                new OrderTransactionStateHandler(
                    new EntityRepositoryMock(),
                    new StateMachineRegistry(
                        new EntityRepositoryMock(),
                        new EntityRepositoryMock(),
                        new EventDispatcherMock(),
                        new DefinitionInstanceRegistryMock([], $this->getContainer())
                    )
                ),
                new TransactionDataHandler(new EntityRepositoryMock()),
                new ConfigReader(new SystemConfigServiceMock($this->getContainer()->get('db_connection'), new EntityRepositoryMock())),
                new EntityRepositoryMock()
            ),
            new NullLogger()
        );
    }
}
