<?php

declare(strict_types=1);

namespace PayonePayment\Test\PaymentHandler;

use DateInterval;
use DateTimeImmutable;
use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Translator;

class PayoneCreditCardPaymentHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    /** @var Translator */
    private $translator;

    protected function setUp(): void
    {
        /** @var Translator $translator */
        $translator       = $this->getContainer()->get('translator');
        $this->translator = $translator;
    }

    public function testRequestOnPay(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVE_CREDIT_CARD => 'on',
        ]);
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository->expects($this->once())->method('saveCard');
        $this->assertNotNull($paymentTransaction->getOrder());

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);

        $this->assertEquals('test-url', $response->getTargetUrl());
    }

    public function testRequestFailureOnPay(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag();
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willThrowException(
            new PayoneRequestException(
                'The message',
                [],
                [
                    'error' => [
                        'CustomerMessage' => 'The message',
                    ],
                ]
            )
        );

        $this->assertNotNull($paymentTransaction->getOrder());
        $this->expectException(AsyncPaymentProcessException::class);

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);
    }

    public function testGeneralFailureOnPay(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag();
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willThrowException(
            new \Exception('The message')
        );

        $this->assertNotNull($paymentTransaction->getOrder());
        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
PayonePayment.errorMessages.genericError');

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);
    }

    public function testRequestOnPayWithoutSave(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag();
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository->expects($this->never())->method('saveCard');

        $this->assertNotNull($paymentTransaction->getOrder());
        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);

        $this->assertEquals($response->getTargetUrl(), 'test-url');
    }

    public function testRequestOnPayWithRedirect(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVE_CREDIT_CARD => 'on',
        ]);
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willReturn(
            [
                'status'      => 'redirect',
                'txid'        => '',
                'userid'      => '',
                'redirecturl' => 'redirect-url',
            ]
        );

        $cardRepository->expects($this->once())->method('saveCard');

        $this->assertNotNull($paymentTransaction->getOrder());
        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);

        $this->assertEquals($response->getTargetUrl(), 'redirect-url');
    }

    public function testRequestOnPaySavedCard(): void
    {
        $client             = $this->createMock(PayoneClientInterface::class);
        $requestFactory     = $this->createMock(RequestParameterFactory::class);
        $cardRepository     = $this->createMock(CardRepositoryInterface::class);
        $dataBag            = $this->getDataBag([
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN => 'saved-pan',
        ]);
        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $cardRepository, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction();

        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => '',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $cardRepository->expects($this->never())->method('saveCard');

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag);

        $this->assertEquals($response->getTargetUrl(), 'test-url');
    }

    private function getPaymentTransaction(): PaymentTransaction
    {
        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId(Constants::LINE_ITEM_ID);

        $orderLineCollection = new OrderLineItemCollection();
        $orderLineCollection->add($orderLineItem);

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setLineItems($orderLineCollection);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    private function getRequestStack(RequestDataBag $dataBag): RequestStack
    {
        $stack = new RequestStack();

        $request = new Request([], $dataBag->all());
        $stack->push($request);

        return $stack;
    }

    private function getDataBag(array $data = []): RequestDataBag
    {
        $dataBag = new RequestDataBag();
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_TRUNCATED_CARD_PAN, '');
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_PSEUDO_CARD_PAN, '');
        $dataBag->set(PayoneCreditCardPaymentHandler::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN, '');
        $dataBag->set(
            PayoneCreditCardPaymentHandler::REQUEST_PARAM_CARD_EXPIRE_DATE,
            (new DateTimeImmutable())->add(new DateInterval('P1Y'))->format('ym')
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
        $configReader = new ConfigReaderMock([
            'creditCardAuthorizationMethod' => 'preauthorization',
        ]);

        return new PayoneCreditCardPaymentHandler(
            $configReader,
            $client,
            $this->translator,
            new TransactionDataHandler($this->createMock(EntityRepositoryInterface::class), new CurrencyPrecision()),
            $this->createMock(EntityRepositoryInterface::class),
            new PaymentStateHandler($this->translator),
            $cardRepository,
            $this->getRequestStack($dataBag),
            $requestFactory
        );
    }

    private function performPayment(
        PayoneCreditCardPaymentHandler $paymentHandler,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag
    ): RedirectResponse {
        return $paymentHandler->pay(
            new AsyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder(),
                ''
            ),
            $dataBag,
            Generator::createSalesChannelContext()
        );
    }
}
