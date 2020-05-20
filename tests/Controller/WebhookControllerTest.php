<?php

declare(strict_types=1);

namespace PayonePayment\Test\Controller;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Controller\WebhookController;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Webhook\Handler\WebhookHandlerInterface;
use PayonePayment\Payone\Webhook\Processor\WebhookProcessor;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Factory\TransactionStatusWebhookHandlerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\HttpFoundation\Request;

class WebhookControllerTest extends TestCase
{
    public function testCreditcardAppointed(): void
    {
        $context             = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannelContext->getSalesChannel()->setId(Defaults::SALES_CHANNEL);

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'appointed');
        $request->request->set('sequencenumber', '0');

        $response = $this->createWebhookController(StateMachineTransitionActions::ACTION_REOPEN, $request->request->all())->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testCreditcardPartialCapture(): void
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

        $response = $this->createWebhookController(StateMachineTransitionActions::ACTION_PAY_PARTIALLY, $request->request->all())->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    public function testCreditcardFullCapture(): void
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

        $response = $this->createWebhookController(StateMachineTransitionActions::ACTION_PAY, $request->request->all())->execute(
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

        $request = new Request();
        $request->request->set('key', md5(''));
        $request->request->set('txid', Constants::PAYONE_TRANSACTION_ID);
        $request->request->set('txaction', 'paid');
        $request->request->set('sequencenumber', '0');

        $response = $this->createWebhookController(StateMachineTransitionActions::ACTION_PAY, $request->request->all())->execute(
            $request,
            $salesChannelContext
        );

        $this->assertEquals(WebhookHandlerInterface::RESPONSE_TSOK, $response->getContent());
    }

    private function createWebhookController(string $transition, array $transactionData): WebhookController
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

        $transactionStatusService = TransactionStatusWebhookHandlerFactory::createTransactionStatusService(
            $stateMachineRegistry,
            []
        );

        $currency = new CurrencyEntity();
        $currency->setId(Constants::CURRENCY_ID);
        $currency->setDecimalPrecision(2);

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

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID     => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER    => 0,
            CustomFieldInstaller::LAST_REQUEST       => 'authorization',
            CustomFieldInstaller::AUTHORIZATION_TYPE => 'authorization',
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);

        $transactionDataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $transactionDataHandler->expects($this->once())->method('getPaymentTransactionByPayoneTransactionId')->willReturn($paymentTransaction);
        $transactionDataHandler->expects($this->once())->method('enhanceStatusWebhookData')->willReturn($transactionData);

        $transactionStatusHandler = TransactionStatusWebhookHandlerFactory::createHandler(
            $transactionStatusService,
            $transactionDataHandler
        );

        return new WebhookController(
            new WebhookProcessor(new ConfigReaderMock([]), new \ArrayObject([$transactionStatusHandler]), new NullLogger())
        );
    }
}
