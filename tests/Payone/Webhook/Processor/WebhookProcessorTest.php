<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Processor;

use PayonePayment\Components\AutomaticCaptureService\AutomaticCaptureServiceInterface;
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
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\Payone\Webhook\Processor\WebhookProcessor
 */
class WebhookProcessorTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAppointsCreditCard(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

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
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(TestDefaults::SALES_CHANNEL);

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
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(TestDefaults::SALES_CHANNEL);

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
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(TestDefaults::SALES_CHANNEL);

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

    protected function getWebhookProcessor(string $transitionName, array $transactionData): WebhookProcessorInterface
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(TestDefaults::SALES_CHANNEL);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())->method('transition')->willReturnCallback(static function (Transition $transition, Context $context) use ($transitionName) {
            static::assertEquals(OrderTransactionDefinition::ENTITY_NAME, $transition->getEntityName());
            static::assertEquals($transitionName, $transition->getTransitionName());
            static::assertEquals('stateId', $transition->getStateFieldName());

            return new StateMachineStateCollection();
        });

        $currency = new CurrencyEntity();
        $currency->setId(Constants::CURRENCY_ID);

        if (method_exists($currency, 'setDecimalPrecision')) {
            $currency->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currency->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true
                )
            );

            $currency->setTotalRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true
                )
            );
        }

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setCurrency($currency);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $payoneTransactionData = new PayonePaymentOrderTransactionDataEntity();
        $payoneTransactionData->assign([
            'transactionId' => Constants::PAYONE_TRANSACTION_ID,
            'sequenceNumber' => 0,
            'lastRequest' => 'authorization',
            'authorizationType' => 'authorization',
        ]);

        $orderTransactionEntity->addExtension(
            PayonePaymentOrderTransactionExtension::NAME,
            $payoneTransactionData
        );

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName('');
        $orderTransactionEntity->setStateMachineState($stateMachineState);

        $configuration = [
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_APPOINTED) => StateMachineTransitionActions::ACTION_REOPEN,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_CANCELATION) => StateMachineTransitionActions::ACTION_CANCEL,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_FAILED) => StateMachineTransitionActions::ACTION_CANCEL,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_DEBIT) => StateMachineTransitionActions::ACTION_REFUND,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PARTIAL_DEBIT) => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PARTIAL_CAPTURE) => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_CAPTURE) => StateMachineTransitionActions::ACTION_PAID,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_PAID) => StateMachineTransitionActions::ACTION_PAID,
            TransactionStatusService::STATUS_PREFIX . ucfirst(TransactionStatusService::ACTION_COMPLETED) => StateMachineTransitionActions::ACTION_PAID,
        ];

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            $configuration,
            $orderTransactionEntity
        );

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects(static::exactly(2))->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects(static::once())->method('getTransactionDataFromWebhook')->willReturn($transactionData);

        $automaticCaptureService = $this->createMock(AutomaticCaptureServiceInterface::class);
        $automaticCaptureService->expects(static::once())->method('captureIfPossible');

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler,
            $automaticCaptureService
        );

        return new WebhookProcessor(
            new ConfigReaderMock([]),
            new \ArrayObject([$transactionStatusHandler]),
            new NullLogger()
        );
    }
}
