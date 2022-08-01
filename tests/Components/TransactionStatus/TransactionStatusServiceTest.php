<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionStatus;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PayonePayment\Test\TestCaseBase\CheckoutTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusServiceTest extends TestCase
{
    use CheckoutTestBehavior;

    /**
     * @dataProvider dataProvider
     * @testdox It executes the $expectedTransitionName transition with config mapping
     */
    public function testItExecutesTransitionWithConfigMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $paymentTransaction  = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                $expectedTransitionName,
                'stateId'
            ),
            $salesChannelContext->getContext()
        );

        $configuration = [
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_APPOINTED)       => StateMachineTransitionActions::ACTION_REOPEN,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_CANCELATION)     => StateMachineTransitionActions::ACTION_CANCEL,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_FAILED)          => StateMachineTransitionActions::ACTION_CANCEL,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_DEBIT)           => StateMachineTransitionActions::ACTION_REFUND,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PARTIAL_DEBIT)   => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PARTIAL_CAPTURE) => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_CAPTURE)         => StateMachineTransitionActions::ACTION_PAID,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PAID)            => StateMachineTransitionActions::ACTION_PAID,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_COMPLETED)       => StateMachineTransitionActions::ACTION_PAID,
        ];

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            $configuration,
            $paymentTransaction->getOrderTransaction()
        );
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    /**
     * @dataProvider dataProvider
     * @testdox It executes the $expectedTransitionName transition with method specific mapping
     */
    public function testItExecutesTransitionWithMethodSpecificMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $paymentTransaction  = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                $expectedTransitionName,
                'stateId'
            ),
            $salesChannelContext->getContext()
        );

        $configuration = [
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_APPOINTED)       => StateMachineTransitionActions::ACTION_REOPEN,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_CANCELATION)     => StateMachineTransitionActions::ACTION_CANCEL,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_FAILED)          => StateMachineTransitionActions::ACTION_CANCEL,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_DEBIT)           => StateMachineTransitionActions::ACTION_REFUND,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_PARTIAL_DEBIT)   => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_PARTIAL_CAPTURE) => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_CAPTURE)         => StateMachineTransitionActions::ACTION_PAID,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_PAID)            => StateMachineTransitionActions::ACTION_PAID,
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD . ucfirst(TransactionStatusService::STATUS_PREFIX) . ucfirst(TransactionStatusService::ACTION_COMPLETED)       => StateMachineTransitionActions::ACTION_PAID,
        ];

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            $configuration,
            $paymentTransaction->getOrderTransaction()
        );
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    /**
     * @dataProvider dataProvider
     * @testdox It not executes the $expectedTransitionName transition without mapping
     */
    public function testItNotExecutesTransitionWithoutMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $paymentTransaction  = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->never())->method('transition');

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [],
            $paymentTransaction->getOrderTransaction()
        );
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    public function dataProvider(): \Generator
    {
        yield [
            'open' => [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => TransactionStatusService::ACTION_APPOINTED,
                'sequencenumber' => '0',
                'receivable'     => '0',
                'price'          => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_REOPEN,
        ];

        yield [
            'capture' => [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => TransactionStatusService::ACTION_CAPTURE,
                'sequencenumber' => '0',
                'receivable'     => '0',
                'price'          => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_PAID,
        ];

        yield [
            'capture' => [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => TransactionStatusService::ACTION_CAPTURE,
                'sequencenumber' => '0',
                'receivable'     => Constants::LINE_ITEM_UNIT_PRICE,
                'price'          => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_PAID,
        ];

        yield [
            'capture_partial' => [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => TransactionStatusService::ACTION_CAPTURE,
                'sequencenumber' => '0',
                'receivable'     => '1',
                'price'          => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        ];

        yield [
            'refund' => [
                'txid'            => Constants::PAYONE_TRANSACTION_ID,
                'txaction'        => TransactionStatusService::ACTION_DEBIT,
                'transactiontype' => TransactionStatusService::TRANSACTION_TYPE_GT,
                'sequencenumber'  => '0',
                'receivable'      => '0',
                'price'           => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_REFUND,
        ];

        yield [
            'refund_partial' => [
                'txid'            => Constants::PAYONE_TRANSACTION_ID,
                'txaction'        => TransactionStatusService::ACTION_DEBIT,
                'transactiontype' => TransactionStatusService::TRANSACTION_TYPE_GT,
                'sequencenumber'  => '0',
                'balance'         => '0',
                'receivable'      => '1',
                'price'           => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
        ];

        yield [
            'refund_partial' => [
                'txid'           => Constants::PAYONE_TRANSACTION_ID,
                'txaction'       => TransactionStatusService::ACTION_CANCELATION,
                'sequencenumber' => '0',
                'balance'        => '0',
                'receivable'     => '1',
                'price'          => Constants::LINE_ITEM_UNIT_PRICE,
            ],
            'transitionName' => StateMachineTransitionActions::ACTION_CANCEL,
        ];
    }
}
