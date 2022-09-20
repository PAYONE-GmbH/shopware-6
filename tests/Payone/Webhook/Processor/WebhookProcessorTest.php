<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Constants;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\Factory\TransactionStatusWebhookHandlerFactory;
use PayonePayment\TestCaseBase\Mock\ConfigReaderMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\Payone\Webhook\Processor\WebhookProcessor
 */
class WebhookProcessorTest extends TestCase
{
    public function testItAppointsCreditCard(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'appointed');
        $request->request->set('sequencenumber', '0');

        $webhookProcessor = $this->getWebhookProcessor(
            StateMachineTransitionActions::ACTION_REOPEN,
            $request->request->all()
        );
        $response = $webhookProcessor->process($salesChannelContext, $request);

        static::assertSame(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testItPartialCapturesCreditCard(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'capture');
        $request->request->set('receivable', '1');
        $request->request->set('sequencenumber', '0');

        $webhookProcessor = $this->getWebhookProcessor(
            StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
            $request->request->all()
        );
        $response = $webhookProcessor->process($salesChannelContext, $request);

        static::assertSame(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testItFullCapturesCreditCard(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'capture');
        $request->request->set('receivable', '0');
        $request->request->set('price', '123.00');
        $request->request->set('sequencenumber', '0');

        $webhookProcessor = $this->getWebhookProcessor(
            StateMachineTransitionActions::ACTION_PAID,
            $request->request->all()
        );
        $response = $webhookProcessor->process($salesChannelContext, $request);

        static::assertSame(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testItProcessesPaidCreditCard(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'paid');
        $request->request->set('sequencenumber', '0');

        $webhookProcessor = $this->getWebhookProcessor(
            StateMachineTransitionActions::ACTION_PAID,
            $request->request->all()
        );
        $response = $webhookProcessor->process($salesChannelContext, $request);

        static::assertSame(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    protected function getWebhookProcessor(string $transition, array $transactionData): WebhookProcessorInterface
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects($this->once())->method('transition')->with(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                Constants::ORDER_TRANSACTION_ID,
                $transition,
                'stateId'
            ),
            $context
        );

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

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

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

        $payoneTransactionData = new PayonePaymentOrderTransactionDataEntity();
        $payoneTransactionData->setTransactionId(Constants::PAYONE_TRANSACTION_ID);
        $payoneTransactionData->setSequenceNumber(0);
        $payoneTransactionData->setLastRequest('authorization');
        $payoneTransactionData->setAuthorizationType('authorization');
        $orderTransactionEntity->addExtension(
            PayonePaymentOrderTransactionExtension::NAME,
            $payoneTransactionData
        );

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName('');
        $orderTransactionEntity->setStateMachineState($stateMachineState);

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
            $orderTransactionEntity
        );

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('getTransactionDataFromWebhook')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        return new WebhookProcessor(
            new ConfigReaderMock([]),
            new \ArrayObject([$transactionStatusHandler]),
            new NullLogger()
        );
    }
}
