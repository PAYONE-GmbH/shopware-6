<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\Postfinance\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractPostfinancePaymentHandlerTest extends AbstractPaymentHandlerTest
{
    abstract protected function getPostfinancePaymentHandler(): string;

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
                'clearingType' => AbstractRequestParameterBuilder::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
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
        return new RequestDataBag();
    }

    protected function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        RequestParameterFactory $requestFactory,
        ?TransactionDataHandlerInterface $dataHandler = null
    ): AbstractPayonePaymentHandler {
        $container = $this->getContainer();
        $className = $this->getPostfinancePaymentHandler();

        return new $className(
            $container->get(ConfigReader::class),
            $this->createMock(EntityRepository::class),
            $container->get(RequestStack::class),
            $client,
            $container->get('translator'),
            $dataHandler ?? $this->createMock(TransactionDataHandlerInterface::class),
            $container->get(PaymentStateHandler::class),
            $requestFactory
        );
    }
}
