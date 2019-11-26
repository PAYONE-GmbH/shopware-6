<?php

declare(strict_types=1);

namespace PayonePayment\Test\Controller;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Controller\WebhookController;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use PayonePayment\Payone\Webhook\Processor\WebhookProcessor;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PayonePayment\Test\Mock\Repository\EntityRepositoryMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

class WebhookControllerTest extends TestCase
{
    /** @var MockObject&OrderTransactionStateHandler */
    private $transactionStateHandler;

    protected function setUp(): void
    {
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
    }

    public function testCreditcardAppointed(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $this->transactionStateHandler->expects($this->once())->method('reopen')->with(Constants::ORDER_TRANSACTION_ID);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'appointed');
        $request->request->set('sequencenumber', '0');

        $response = $this->createWebhookController()->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testCreditcardCapture(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $this->transactionStateHandler->expects($this->once())->method('pay')->with(Constants::ORDER_TRANSACTION_ID);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'capture');
        $request->request->set('receivable', '1');
        $request->request->set('sequencenumber', '0');

        $response = $this->createWebhookController()->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testCreditcardPaid(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $this->transactionStateHandler->expects($this->once())->method('pay')->with(Constants::ORDER_TRANSACTION_ID);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'paid');
        $request->request->set('sequencenumber', '0');

        $response = $this->createWebhookController()->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    private function createWebhookController(): WebhookController
    {
        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $this->createMock(EntityRepositoryInterface::class),
            $this->transactionStateHandler,
            new TransactionDataHandler(new EntityRepositoryMock()),
            []
        );
        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService
        );

        return new WebhookController(
            new WebhookProcessor(new ConfigReaderMock([]), new \ArrayObject([$transactionStatusHandler]), new NullLogger())
        );
    }
}
