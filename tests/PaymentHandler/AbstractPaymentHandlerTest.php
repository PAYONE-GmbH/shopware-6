<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testSuccessfulPayment(): void
    {
        $client         = $this->createMock(PayoneClientInterface::class);
        $requestFactory = $this->createMock(RequestParameterFactory::class);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getSuccessfulRequestDataBag();

        $dataHandler    = $this->createMock(TransactionDataHandler::class);
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory, $dataHandler);

        $responseData = [
            'status' => 'OK',
            'txid'   => 'test-transaction-id',
            'userid' => 'test-user-id',
        ];

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $responseData['redirecturl'] = 'test-redirect-url';
        }

        $client->method('request')->willReturn(array_merge($responseData, $this->getSuccessfulResponseData()));

        $requestFactory->method('getRequestParameter')->willReturn(array_merge([
            'request' => [],
        ], $this->getSuccessfulRequestParameter()));

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            get_class($paymentHandler)
        );

        // test if the data handler got called, and if the basic information would be set on the order
        $dataHandler->expects(self::once())
            ->method('saveTransactionData')
            ->willReturnCallback(function (PaymentTransaction $transaction, Context $context, array $data): void {
                self::assertArrayHasKey(CustomFieldInstaller::AUTHORIZATION_TYPE, $data);
                self::assertArrayHasKey(CustomFieldInstaller::LAST_REQUEST, $data);
                self::assertArrayHasKey(CustomFieldInstaller::TRANSACTION_ID, $data);
                self::assertArrayHasKey(CustomFieldInstaller::SEQUENCE_NUMBER, $data);
                self::assertArrayHasKey(CustomFieldInstaller::USER_ID, $data);
                self::assertArrayHasKey(CustomFieldInstaller::TRANSACTION_STATE, $data);
                self::assertArrayHasKey(CustomFieldInstaller::ALLOW_CAPTURE, $data);
                self::assertArrayHasKey(CustomFieldInstaller::CAPTURED_AMOUNT, $data);
                self::assertArrayHasKey(CustomFieldInstaller::ALLOW_REFUND, $data);
                self::assertArrayHasKey(CustomFieldInstaller::REFUNDED_AMOUNT, $data);

                $this->assertSuccessfulTransactionData($data);
            });

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        $this->assertSuccessfulResponse($response);
    }

    public function testUnSuccessfulPaymentByStatus(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getSuccessfulRequestDataBag();
        $paymentHandler      = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $client->method('request')->willReturn([
            'status' => 'ERROR',
        ]);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            get_class($paymentHandler)
        );

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = AsyncPaymentProcessException::class;
        } else {
            $expectedException = SyncPaymentProcessException::class;
        }

        self::expectException($expectedException);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testUnSuccessfulPaymentByInvalidStatus(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getSuccessfulRequestDataBag();
        $paymentHandler      = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $client->method('request')->willReturn([
            'status' => 'invalid-status',
        ]);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            get_class($paymentHandler)
        );

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = AsyncPaymentProcessException::class;
        } else {
            $expectedException = SyncPaymentProcessException::class;
        }

        self::expectException($expectedException);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    /**
     * test if payment handler catches the PayoneException and converts it to a shopware exception
     * and if the customer-message of the payone-response is contained in the shopware exception message
     */
    public function testUnSuccessfulPaymentByException(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getSuccessfulRequestDataBag();
        $paymentHandler      = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $exception = new PayoneRequestException(
            'test-exception',
            [],
            [
                'error' => [
                    'CustomerMessage' => 'test-customer-message',
                ],
            ]
        );

        $client->method('request')->willThrowException($exception);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            get_class($paymentHandler)
        );

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = AsyncPaymentProcessException::class;
        } else {
            $expectedException = SyncPaymentProcessException::class;
        }

        self::expectException($expectedException);
        self::expectExceptionMessageMatches('/.*test-customer-message.*/');
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    /**
     * test if payment handler catches a random throwable and converts it to a shopware exception
     */
    public function testUnSuccessfulPaymentByThrowable(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getSuccessfulRequestDataBag();
        $paymentHandler      = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $exception = $this->createMock(\Throwable::class);

        $client->method('request')->willThrowException($exception);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            get_class($paymentHandler)
        );

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = AsyncPaymentProcessException::class;
        } else {
            $expectedException = SyncPaymentProcessException::class;
        }

        self::expectException($expectedException);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    /**
     * returns simulated api response from payone
     */
    abstract protected function getSuccessfulResponseData(): array;

    /**
     * returns request-data-bag which would be send by the browser
     */
    abstract protected function getSuccessfulRequestDataBag(): RequestDataBag;

    /**
     * returns request parameter which would be sent to payone during payment
     */
    abstract protected function getSuccessfulRequestParameter(): array;

    /**
     * performs additional tests for successful (simulated) response
     * `$response` may be null, if it is a sync payment
     */
    abstract protected function assertSuccessfulResponse($response = null);

    /**
     * performs additional tests for the transaction data which would be written to the order
     */
    abstract protected function assertSuccessfulTransactionData(array $transactionData);

    abstract protected function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        RequestParameterFactory $requestFactory,
        TransactionDataHandlerInterface $dataHandler = null
    ): AbstractPayonePaymentHandler;

    protected function performPayment(
        AbstractPayonePaymentHandler $paymentHandler,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ) {
        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $struct = new AsyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder(),
                ''
            );
        } elseif ($paymentHandler instanceof SynchronousPaymentHandlerInterface) {
            $struct = new SyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder()
            );
        } else {
            throw new \RuntimeException('invalid type of provided payment handler: ' . get_class($paymentHandler));
        }

        return $paymentHandler->pay($struct, $dataBag, $salesChannelContext);
    }
}
