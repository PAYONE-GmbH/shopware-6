<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Webhook\Handler;

use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

class TransactionStatusWebhookHandlerTest extends TestCase
{
    use KernelTestBehaviour;

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

        TransactionStatusWebhookHandlerFactory::createHandler(
            $this->createMock(EntityRepository::class),
            $this->transactionStateHandler
        )->process(
            $salesChannelContext,
            [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => 'appointed',
                'sequencenumber' => '0',
            ]
        );
    }
}
