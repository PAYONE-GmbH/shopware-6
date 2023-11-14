<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractKlarnaPaymentHandlerTest extends AbstractPaymentHandlerTest
{
    public function testItThrowsExceptionOnMissingToken(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::exactly(0))->method('getRequestParameter'); // validation for token should be performed before parameter build

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = new RequestDataBag(); // missing token
        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->createMock(OrderEntity::class),
            $paymentHandler::class
        );

        if ($paymentHandler instanceof AsynchronousPaymentHandlerInterface) {
            $expectedException = AsyncPaymentProcessException::class;
        } else {
            $expectedException = SyncPaymentProcessException::class;
        }

        $this->expectException($expectedException);
        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    abstract protected function getKlarnaPaymentHandler(): string;

    protected function getSuccessfulResponseData(): array
    {
        return [];
    }

    protected function assertSuccessfulResponse($response = null): void
    {
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('test-redirect-url', $response->getTargetUrl());
    }

    protected function assertSuccessfulTransactionData(array $transactionData): void
    {
        Assert::assertArraySubset(
            [
                'authorizationType' => 'authorization',
                'lastRequest' => 'authorization',
                'transactionId' => 'test-transaction-id',
                'sequenceNumber' => -1,
                'userId' => 'test-user-id',
                'transactionState' => 'REDIRECT',
                'clearingType' => 'fnc',
            ],
            $transactionData
        );

        static::assertArrayHasKey('request', array_values($transactionData['transactionData'])[0]);
        static::assertArrayHasKey('response', array_values($transactionData['transactionData'])[0]);
    }

    protected function getSuccessfulRequestParameter(): array
    {
        return [];
    }

    protected function getSuccessfulRequestDataBag(): RequestDataBag
    {
        return new RequestDataBag([
            'payoneKlarnaAuthorizationToken' => 'test-token',
        ]);
    }

    protected function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        RequestParameterFactory $requestFactory,
        ?TransactionDataHandlerInterface $transactionDataHandler = null,
        ?OrderActionLogDataHandlerInterface $orderActionLogDataHandler = null
    ): AbstractPayonePaymentHandler {
        $container = $this->getContainer();
        $className = $this->getKlarnaPaymentHandler();

        return new $className(
            $container->get(ConfigReader::class),
            $this->createMock(EntityRepository::class),
            $container->get(RequestStack::class),
            $client,
            $container->get('translator'),
            $transactionDataHandler ?? $this->createMock(TransactionDataHandlerInterface::class),
            $orderActionLogDataHandler ?? $this->createMock(OrderActionLogDataHandlerInterface::class),
            $container->get(PaymentStateHandler::class),
            $requestFactory,
            $this->createMock(CartHasher::class)
        );
    }
}
