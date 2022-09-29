<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Installer\CustomFieldInstaller;
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
    public function testUnSuccessfulPaymentByMissingToken(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->exactly(0))->method('getRequestParameter'); // validation for token should be performed before parameter build

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = new RequestDataBag(); // missing token
        $paymentHandler      = $this->getPaymentHandler($client, $dataBag, $requestFactory);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->createMock(OrderEntity::class),
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

    abstract protected function getKlarnaPaymentHandler(): string;

    protected function getSuccessfulResponseData(): array
    {
        return [];
    }

    protected function assertSuccessfulResponse($response = null): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('test-redirect-url', $response->getTargetUrl());
    }

    protected function assertSuccessfulTransactionData(array $transactionData): void
    {
        self::assertArrayHasKey(CustomFieldInstaller::CLEARING_TYPE, $transactionData);
        self::assertEquals(AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC, $transactionData[CustomFieldInstaller::CLEARING_TYPE]);
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
        TransactionDataHandlerInterface $dataHandler = null
    ): AbstractPayonePaymentHandler {
        $container = $this->getContainer();
        $className = $this->getKlarnaPaymentHandler();

        return new $className(
            $container->get(ConfigReader::class),
            $this->createMock(EntityRepository::class),
            $container->get(RequestStack::class),
            $container->get('translator'),
            $requestFactory,
            $client,
            $dataHandler ?? $this->createMock(TransactionDataHandlerInterface::class),
            $this->createMock(CartHasher::class),
            $container->get(PaymentStateHandler::class),
        );
    }
}
