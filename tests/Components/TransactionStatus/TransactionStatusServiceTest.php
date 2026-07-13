<?php

declare(strict_types=1);

namespace PayonePayment\Tests\Components\TransactionStatus;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;
use PayonePayment\Provider\Payone\PaymentMethod\CreditCardPaymentMethod;
use PayonePayment\Service\CurrencyPrecisionService;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

/**
 * @covers \PayonePayment\Components\TransactionStatus\TransactionStatusService
 */
class TransactionStatusServiceTest extends TestCase
{
    private const TRANSACTION_ID = 'aa9a1a0e6b5648f6a17f9c0e5d9d1c11';

    private const DEFAULT_CONFIGURATION = [
        'paymentStatusAppointed' => 'reopen',
        'paymentStatusPaid'      => 'paid',
        'paymentStatusFailed'    => 'cancel',
        'paymentStatusCapture'   => 'paid',
    ];

    /**
     * Fall 1: The transaction is still open (customer was redirected, e.g. for Wero or a 3-D Secure
     * challenge) and PAYONE later reports a genuine failure. The configured "failed" mapping must run.
     */
    public function testFailedNotificationWhileOpenExecutesConfiguredCancelMapping(): void
    {
        $orderTransaction = $this->createOrderTransaction(OrderTransactionStates::STATE_OPEN);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())->method('transition')->with(
            new Transition(OrderTransactionDefinition::ENTITY_NAME, self::TRANSACTION_ID, 'cancel', 'stateId'),
            static::isInstanceOf(Context::class),
        );

        $service = $this->createService($orderTransaction, $stateMachineRegistry);

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'failed', 'txid' => '1'],
        );
    }

    /**
     * Reproduces the originally reported Wero defect literally: the first ever stored PAYONE
     * response was "REDIRECT" (as it is for every redirect-based payment method, e.g. Wero or a
     * 3-D Secure credit card challenge) and PAYONE then reports a genuine "failed". While the
     * transaction is still open, the configured cancel mapping must run regardless of that
     * redirect history.
     */
    public function testFailedNotificationAfterInitialRedirectResponseStillExecutesConfiguredMapping(): void
    {
        $orderTransaction = $this->createOrderTransaction(OrderTransactionStates::STATE_OPEN);

        $payoneTransactionData = new PayonePaymentOrderTransactionDataEntity();
        $payoneTransactionData->assign([
            'id'              => 'payone-data-id',
            'transactionData' => [
                '2026-07-01T10:00:00+00:00' => [
                    'request'  => ['request' => 'preauthorization'],
                    'response' => ['status' => 'REDIRECT'],
                ],
            ],
        ]);
        $orderTransaction->addExtension(PayonePaymentOrderTransactionExtension::NAME, $payoneTransactionData);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())->method('transition')->with(
            new Transition(OrderTransactionDefinition::ENTITY_NAME, self::TRANSACTION_ID, 'cancel', 'stateId'),
            static::isInstanceOf(Context::class),
        );

        $service = $this->createService($orderTransaction, $stateMachineRegistry);

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'failed', 'txid' => '1'],
        );
    }

    /**
     * Fall 2: The transaction is still open and PAYONE reports a genuine success. The configured
     * mapping for the reported action must run as normal. This does not build any redirect history -
     * it only covers the general "successful webhook while open" mapping, unaffected by the guard.
     */
    public function testSuccessNotificationWhileOpenExecutesConfiguredMapping(): void
    {
        $orderTransaction = $this->createOrderTransaction(OrderTransactionStates::STATE_OPEN);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())->method('transition')->with(
            new Transition(OrderTransactionDefinition::ENTITY_NAME, self::TRANSACTION_ID, 'paid', 'stateId'),
            static::isInstanceOf(Context::class),
        );

        $service = $this->createService($orderTransaction, $stateMachineRegistry);

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'paid', 'txid' => '1'],
        );
    }

    /**
     * Fall 3: The transaction already reached one of the protected states. A later "failed"
     * notification is treated as late or duplicated and must be ignored instead of rolling the
     * payment status back.
     *
     * @dataProvider protectedStateProvider
     */
    public function testFailedNotificationIsIgnoredForProtectedState(string $currentState): void
    {
        $orderTransaction = $this->createOrderTransaction($currentState);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::never())->method('transition');

        $service = $this->createService($orderTransaction, $stateMachineRegistry);

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'failed', 'txid' => '1'],
        );
    }

    public function protectedStateProvider(): \Generator
    {
        yield 'paid' => [OrderTransactionStates::STATE_PAID];
        yield 'paid_partially' => [OrderTransactionStates::STATE_PARTIALLY_PAID];
        yield 'authorized' => [OrderTransactionStates::STATE_AUTHORIZED];
        yield 'refunded' => [OrderTransactionStates::STATE_REFUNDED];
        yield 'refunded_partially' => [OrderTransactionStates::STATE_PARTIALLY_REFUNDED];
        yield 'chargeback' => [OrderTransactionStates::STATE_CHARGEBACK];
    }

    /**
     * Fall 4: The customer aborted the redirect themselves. That flow already brought the
     * transaction to "cancelled" via the return-URL/finalize flow (outside of this service). A
     * subsequent duplicate "failed" webhook re-attempts the same "cancel" transition; the state
     * machine rejects the now-illegal "cancelled -> cancelled" transition, which must be handled
     * gracefully instead of bubbling up as an error.
     */
    public function testFailedNotificationAfterCustomerAbortIsHandledGracefully(): void
    {
        $orderTransaction = $this->createOrderTransaction(OrderTransactionStates::STATE_CANCELLED);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())
            ->method('transition')
            ->willThrowException(new IllegalTransitionException(OrderTransactionStates::STATE_CANCELLED, 'cancel', []));

        $service = $this->createService($orderTransaction, $stateMachineRegistry);

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'failed', 'txid' => '1'],
        );

        $this->addToAssertionCount(1);
    }

    /**
     * Fall 5: The guard lives centrally in TransactionStatusService, not in any payment-method-specific
     * code. This does not build a 3-D Secure or redirect history - it only proves that a
     * payment-method-specific status mapping (here: credit card) is still resolved and executed
     * correctly while the transaction is open, i.e. the guard does not interfere with that resolution.
     */
    public function testFailedNotificationUsesPaymentMethodSpecificMappingWhileOpen(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setHandlerIdentifier(CreditCardPaymentHandler::class);

        $orderTransaction = $this->createOrderTransaction(OrderTransactionStates::STATE_OPEN, $paymentMethod);

        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $stateMachineRegistry->expects(static::once())->method('transition')->with(
            new Transition(OrderTransactionDefinition::ENTITY_NAME, self::TRANSACTION_ID, 'cancel', 'stateId'),
            static::isInstanceOf(Context::class),
        );

        $paymentMethodRegistry = new PaymentMethodRegistry([new CreditCardPaymentMethod()]);

        $configuration = \array_merge(self::DEFAULT_CONFIGURATION, [
            'creditCardPaymentStatusFailed' => 'cancel',
        ]);

        $service = $this->createService(
            $orderTransaction,
            $stateMachineRegistry,
            $paymentMethodRegistry,
            $configuration,
        );

        $service->transitionByConfigMapping(
            $this->createSalesChannelContext(),
            $this->createPaymentTransaction($orderTransaction),
            ['txaction' => 'failed', 'txid' => '1'],
        );
    }

    private function createService(
        OrderTransactionEntity $orderTransaction,
        StateMachineRegistry $stateMachineRegistry,
        PaymentMethodRegistry|null $paymentMethodRegistry = null,
        array $configuration = self::DEFAULT_CONFIGURATION,
    ): TransactionStatusService {
        // The guard reuses the transaction/state already loaded by executeTransition(), so exactly
        // one repository read is expected per transitionByConfigMapping() call in these scenarios.
        $transactionRepository = $this->createMock(EntityRepository::class);
        $transactionRepository->expects(static::once())->method('search')->willReturn(
            $this->createSearchResult($orderTransaction),
        );

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->method('read')->willReturn(new Configuration($configuration));

        return new TransactionStatusService(
            $stateMachineRegistry,
            $configReader,
            $transactionRepository,
            $this->createMock(LoggerInterface::class),
            new CurrencyPrecisionService(),
            $paymentMethodRegistry ?? new PaymentMethodRegistry(),
        );
    }

    private function createOrderTransaction(
        string $currentState,
        PaymentMethodEntity|null $paymentMethod = null,
    ): OrderTransactionEntity {
        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setId('state-' . $currentState);
        $stateMachineState->setTechnicalName($currentState);

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->assign(['id' => self::TRANSACTION_ID]);
        $orderTransaction->setStateMachineState($stateMachineState);

        if (null !== $paymentMethod) {
            $orderTransaction->setPaymentMethod($paymentMethod);
        }

        return $orderTransaction;
    }

    private function createPaymentTransaction(OrderTransactionEntity $orderTransaction): PaymentTransaction
    {
        $order = new OrderEntity();
        $order->setCurrency(new CurrencyEntity());

        return PaymentTransaction::fromOrderTransaction($orderTransaction, $order);
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->assign(['id' => 'sales-channel-id']);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn(Context::createDefaultContext());
        $salesChannelContext->method('getSalesChannel')->willReturn($salesChannel);

        return $salesChannelContext;
    }

    private function createSearchResult(OrderTransactionEntity $orderTransaction): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->setIds([self::TRANSACTION_ID]);

        return new EntitySearchResult(
            OrderTransactionDefinition::ENTITY_NAME,
            1,
            new EntityCollection([$orderTransaction]),
            null,
            $criteria,
            Context::createDefaultContext(),
        );
    }
}
