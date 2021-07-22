<?php

declare(strict_types=1);

namespace PayonePayment\Test\Components;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStatusTest extends TestCase
{
    use KernelTestBehaviour;

    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

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

    /**
     * @dataProvider dataProvider
     */
    public function testTransactionStatusWithMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext  = $this->getSalesChannelContext();
        $paymentTransaction   = $this->getPaymentTransaction();
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService($stateMachineRegistry, $configuration, $paymentTransaction->getOrderTransaction());
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTransactionStatusWithMethodSpecificMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext  = $this->getSalesChannelContext();
        $paymentTransaction   = $this->getPaymentTransaction();
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService($stateMachineRegistry, $configuration, $paymentTransaction->getOrderTransaction());
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTransactionStatusWithoutMapping(array $transactionData, string $expectedTransitionName): void
    {
        $salesChannelContext  = $this->getSalesChannelContext();
        $paymentTransaction   = $this->getPaymentTransaction();
        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->never())->method('transition');

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService($stateMachineRegistry, [], $paymentTransaction->getOrderTransaction());
        $transactionStatusService->transitionByConfigMapping($salesChannelContext, $paymentTransaction, $transactionData);
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        return $salesChannelContext;
    }

    private function getPaymentTransaction(): PaymentTransaction
    {
        $currencyMock = new CurrencyEntity();
        $currencyMock->setId(Constants::CURRENCY_ID);

        if (method_exists($currencyMock, 'setDecimalPrecision')) {
            $currencyMock->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currencyMock->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );

            $currencyMock->setTotalRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );
        }

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setCurrency($currencyMock);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);
        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID     => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER    => 0,
            CustomFieldInstaller::LAST_REQUEST       => 'authorization',
            CustomFieldInstaller::AUTHORIZATION_TYPE => 'authorization',
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName('');
        $orderTransactionEntity->setStateMachineState($stateMachineState);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }
}
