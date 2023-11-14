<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\Mock\ConfigReaderMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler
 */
class PayoneCreditCardPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentAndReturnsCorrectRedirectUrl(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'success',
                'txid' => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
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
        $dataBag = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_TRUNCATED_CARD_PAN => 'the-card-pan',
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_PSEUDO_CARD_PAN => 'the-pseudo-card-pan',
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_TYPE => 'the-card-type',
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVE_CREDIT_CARD => 'on',
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'success',
                'txid' => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects(static::once())->method('saveCard')->with(
            $salesChannelContext->getCustomer(),
            'the-card-pan',
            'the-pseudo-card-pan',
            'the-card-type',
            static::anything(),
            static::anything()
        );

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects(static::once())->method('saveTransactionData')->with(
            static::anything(),
            static::anything(),
            static::callback(static function ($parameter) {
                static::assertIsArray($parameter);
                static::assertSame('the-card-type', $parameter['additionalData']['card_type']);

                return true;
            })
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory, $dataHandler);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentAndNotSavesCard(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'success',
                'txid' => '',
                'userid' => '',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects(static::never())->method('saveCard');

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneCreditCardPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentWithRedirect(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = $this->getDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'redirect',
                'txid' => '',
                'userid' => '',
                'redirecturl' => 'redirect-url',
            ]
        );

        $cardRepository = $this->createMock(CardRepositoryInterface::class);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
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
        $dataBag = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN => 'saved-pan',
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'success',
                'txid' => '',
                'userid' => '',
            ]
        );

        $savedCard = new PayonePaymentCardEntity();
        $savedCard->setCardType('the-card-type');
        $cardRepository = $this->createMock(CardRepositoryInterface::class);
        $cardRepository->expects(static::never())->method('saveCard');
        $cardRepository->expects(static::once())->method('getExistingCard')->willReturn($savedCard);

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects(static::once())->method('saveTransactionData')->with(
            static::anything(),
            static::anything(),
            static::callback(static function ($parameter) {
                static::assertIsArray($parameter);
                static::assertSame('the-card-type', $parameter['additionalData']['card_type']);

                return true;
            })
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory, $dataHandler);
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
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_TYPE, '');
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
        RequestParameterFactory $requestFactory,
        ?TransactionDataHandlerInterface $transactionDataHandler = null,
        ?OrderActionLogDataHandlerInterface $orderActionLogDataHandler = null
    ): PayoneCreditCardPaymentHandler {
        $translator = $this->getContainer()->get('translator');
        $configReader = new ConfigReaderMock([
            'creditCardAuthorizationMethod' => 'preauthorization',
        ]);

        if (!$transactionDataHandler) {
            $transactionDataHandler = new TransactionDataHandler(
                $this->createMock(EntityRepository::class),
                new CurrencyPrecision()
            );
        }

        return new PayoneCreditCardPaymentHandler(
            $configReader,
            $this->createMock(EntityRepository::class),
            $this->getRequestStack($dataBag),
            $client,
            $translator,
            $transactionDataHandler,
            $orderActionLogDataHandler ?? $this->createMock(OrderActionLogDataHandlerInterface::class),
            new PaymentStateHandler($translator),
            $requestFactory,
            $cardRepository
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
