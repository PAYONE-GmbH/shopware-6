<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Constants;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\TestCaseBase\Factory\TransactionStatusWebhookHandlerFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\Payone\Webhook\Handler\TransactionStatusWebhookHandler
 */
class TransactionStatusWebhookHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAppointsCreditCardWithoutMapping(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->never())->method('transition');

        $orderEntity        = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction = $this->getPaymentTransaction($orderEntity, PayoneCreditCardPaymentHandler::class);

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [],
            $paymentTransaction->getOrderTransaction()
        );

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getTransactionDataFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        $transactionStatusHandler->process(
            $salesChannelContext,
            new Request([], $transactionData)
        );
    }

    public function testItAppointsCreditCardWithMapping(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                'paid',
                'stateId'
            ),
            $salesChannelContext->getContext()
        );

        $orderEntity        = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction = $this->getPaymentTransaction($orderEntity, PayoneCreditCardPaymentHandler::class);

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [
                'paymentStatusAppointed' => 'paid',
            ],
            $paymentTransaction->getOrderTransaction()
        );

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getTransactionDataFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        $transactionStatusHandler->process(
            $salesChannelContext,
            new Request([], $transactionData)
        );
    }

    public function testItAppointsCreditCardWithSpecificMapping(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                'paid',
                'stateId'
            ),
            $salesChannelContext->getContext()
        );

        $orderEntity        = $this->getRandomOrder($salesChannelContext);
        $paymentTransaction = $this->getPaymentTransaction($orderEntity, PayoneCreditCardPaymentHandler::class);

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [
                'creditCardPaymentStatusAppointed' => 'paid',
            ],
            $paymentTransaction->getOrderTransaction()
        );

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getTransactionDataFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        $transactionStatusHandler->process(
            $salesChannelContext,
            new Request([], $transactionData)
        );
    }
}
