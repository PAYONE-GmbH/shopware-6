<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
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
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsSuccessfulPayment(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);
        $requestFactory = $this->createMock(RequestParameterFactory::class);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getSuccessfulRequestDataBag();

        $transactionDataHandler = $this->createMock(TransactionDataHandler::class);
        $orderActionLogDataHandler = $this->createMock(OrderActionLogDataHandlerInterface::class);
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory, $transactionDataHandler, $orderActionLogDataHandler);

        $responseData = [
            'status' => 'REDIRECT',
            'txid' => 'test-transaction-id',
            'userid' => 'test-user-id',
        ];

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $responseData['redirecturl'] = 'test-redirect-url';
        }

        $client->method('request')->willReturn(array_merge($responseData, $this->getSuccessfulResponseData()));

        $requestFactory->method('getRequestParameter')->willReturn(array_merge([
            'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
        ], $this->getSuccessfulRequestParameter()));

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            $paymentHandler::class
        );

        // test if the data handler got called, and if the basic information would be set on the order
        $transactionDataHandler->expects(static::once())->method('saveTransactionData')->with(
            static::anything(),
            static::anything(),
            static::callback(function ($transactionData) {
                $this->assertSuccessfulTransactionData($transactionData);

                return true;
            })
        );

        $orderActionLogDataHandler->expects(static::once())->method('createOrderActionLog');

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        $this->assertSuccessfulResponse($response);
    }

    public function testItThrowsExceptionOnErrorStatus(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getSuccessfulRequestDataBag();
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $client->method('request')->willReturn([
            'status' => 'ERROR',
        ]);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            $paymentHandler::class
        );

        $this->expectedPaymentInterruptedException($paymentHandler);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItThrowsExceptionOnInvalidStatus(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getSuccessfulRequestDataBag();
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $client->method('request')->willReturn([
            'status' => 'invalid-status',
        ]);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            $paymentHandler::class
        );

        $this->expectedPaymentInterruptedException($paymentHandler);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    /**
     * test if payment handler catches the PayoneException and converts it to a shopware exception
     * and if the customer-message of the payone-response is contained in the shopware exception message
     */
    public function testItHandlesPayoneExceptionsCorrectly(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getSuccessfulRequestDataBag();
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);

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
            $paymentHandler::class
        );

        $this->expectedPaymentInterruptedException($paymentHandler);
        $this->expectExceptionMessageMatches('/.*test-customer-message.*/');
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    /**
     * test if payment handler catches a random throwable and converts it to a shopware exception
     */
    public function testItHandlesRandomThrowableCorrectly(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->method('getRequestParameter')->willReturn([]);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getSuccessfulRequestDataBag();
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $exception = $this->createMock(\Throwable::class);

        $client->method('request')->willThrowException($exception);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            $paymentHandler::class
        );

        $this->expectedPaymentInterruptedException($paymentHandler);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    protected function expectedPaymentInterruptedException(AbstractPayonePaymentHandler $paymentHandler): void
    {
        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = class_exists(AsyncPaymentProcessException::class) ? AsyncPaymentProcessException::class : PaymentException::class;
        } elseif ($paymentHandler instanceof SynchronousPaymentHandlerInterface) {
            $expectedException = class_exists(SyncPaymentProcessException::class) ? SyncPaymentProcessException::class : PaymentException::class;
        } else {
            throw new \RuntimeException('invalid payment handler ' . $paymentHandler::class);
        }

        $this->expectException($expectedException);
    }

    /**
     * returns simulated api response from payone
     */
    abstract protected function getSuccessfulResponseData(): array;

    /**
     * returns request-data-bag which would be sent by the browser
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
        ?TransactionDataHandlerInterface $transactionDataHandler = null,
        ?OrderActionLogDataHandlerInterface $orderActionLogDataHandler = null
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

            return $paymentHandler->pay($struct, $dataBag, $salesChannelContext);
        }

        if ($paymentHandler instanceof SynchronousPaymentHandlerInterface) {
            $struct = new SyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder()
            );
            $paymentHandler->pay($struct, $dataBag, $salesChannelContext);
        } else {
            throw new \RuntimeException('invalid type of provided payment handler: ' . $paymentHandler::class);
        }
    }
}
