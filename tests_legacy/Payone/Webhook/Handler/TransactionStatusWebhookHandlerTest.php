<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Webhook\Handler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\HttpFoundation\Request;

class TransactionStatusWebhookHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    /** @var MockObject&OrderTransactionStateHandler */
    private $transactionStateHandler;

    protected function setUp(): void
    {
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
    }

    public function testCreditcardAppointedWithoutMapping(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->never())->method('transition');

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $currency = new CurrencyEntity();
        $currency->setId(Constants::CURRENCY_ID);

        if (method_exists($currency, 'setDecimalPrecision')) {
            $currency->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currency->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );

            $currency->setTotalRounding(
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
        $orderEntity->setCurrency($currency);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [],
            $orderTransactionEntity
        );

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getCustomFieldsFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        $transactionStatusHandler->process(
            $salesChannelContext,
            new Request([], $transactionData)
        );
    }

    public function testCreditcardAppointedWithMapping(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                'paid',
                'stateId'
            ),
            $context
        );

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $currency = new CurrencyEntity();
        $currency->setId(Constants::CURRENCY_ID);

        if (method_exists($currency, 'setDecimalPrecision')) {
            $currency->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currency->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );

            $currency->setTotalRounding(
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
        $orderEntity->setCurrency($currency);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [
                'paymentStatusAppointed' => 'paid',
            ],
            $orderTransactionEntity
        );

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getCustomFieldsFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        $transactionStatusHandler->process(
            $salesChannelContext,
            new Request([], $transactionData)
        );
    }

    public function testCreditcardAppointedWithSpecificMapping(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                'paid',
                'stateId'
            ),
            $context
        );

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $currency = new CurrencyEntity();
        $currency->setId(Constants::CURRENCY_ID);

        if (method_exists($currency, 'setDecimalPrecision')) {
            $currency->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currency->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );

            $currency->setTotalRounding(
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
        $orderEntity->setCurrency($currency);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            [
                'creditCardPaymentStatusAppointed' => 'paid',
            ],
            $orderTransactionEntity
        );

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionData = [
            'txid'           => Constants::PAYONE_TRANSACTION_ID,
            'txaction'       => 'appointed',
            'sequencenumber' => '0',
        ];

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getCustomFieldsFromWebhook')->willReturn($transactionData);

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
