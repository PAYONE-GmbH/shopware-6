<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\Mock\ConfigReaderMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PayoneCreditCardPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentAndReturnsCorrectRedirectUrl(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('test-url', $response->getTargetUrl());
    }

    public function testItPerformsPaymentAndSavesCard(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVE_CREDIT_CARD => 'on',
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects($this->once())->method('saveCard');

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentAndNotSavesCard(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects($this->never())->method('saveCard');

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentWithRedirect(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status'      => 'redirect',
                'txid'        => '',
                'userid'      => '',
                'redirecturl' => 'redirect-url',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('redirect-url', $response->getTargetUrl());
    }

    public function testItPerformsPaymentWithSavedCard(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN => 'saved-pan',
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects($this->never())->method('saveCard');

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('test-url', $response->getTargetUrl());
    }

    private function getDataBag(array $data = []): RequestDataBag
    {
        $dataBag = new RequestDataBag();
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_TRUNCATED_CARD_PAN, '');
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_PSEUDO_CARD_PAN, '');
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN, '');
        $dataBag->set(
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_EXPIRE_DATE,
            (new \DateTimeImmutable())->add(new \DateInterval('P1Y'))->format('ym')
        );

        foreach ($data as $key => $value) {
            $dataBag->set($key, $value);
        }

        return $dataBag;
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        CardRepositoryInterface $cardRepository,
        RequestParameterFactory $requestFactory
    ): PayoneCreditCardPaymentHandler {
        $translator   = $this->getContainer()->get('translator');
        $configReader = new ConfigReaderMock([
            'creditCardAuthorizationMethod' => 'preauthorization',
        ]);

        return new PayoneCreditCardPaymentHandler(
            $configReader,
            $client,
            $translator,
            new TransactionDataHandler($this->createMock(EntityRepositoryInterface::class), new CurrencyPrecision()),
            $this->createMock(EntityRepositoryInterface::class),
            new PaymentStateHandler($translator),
            $cardRepository,
            $this->getRequestStack($dataBag),
            $requestFactory
        );
    }

    private function performPayment(
        PayoneCreditCardPaymentHandler $paymentHandler,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        return $paymentHandler->pay(
            new AsyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder(),
                ''
            ),
            $dataBag,
            $salesChannelContext
        );
    }
}
