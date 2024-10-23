<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\CustomerDataPersistor\CustomerDataPersistor;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandler;
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
 * @covers \PayonePayment\PaymentHandler\PayonePaypalV2PaymentHandler
 */
class PayonePaypalV2PaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentAndReturnsCorrectRedirectUrl(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = new RequestDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'test-status',
                'txid' => '',
                'userid' => '',
            ]
        );

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler = $this->getPaymentHandler($client, $dataBag, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayonePaypalV2PaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('test-url', $response->getTargetUrl());
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        RequestParameterFactory $requestFactory
    ): PayonePaypalV2PaymentHandler {
        $translator = $this->getContainer()->get('translator');
        $configReader = new ConfigReaderMock([
            'paypalV2AuthorizationMethod' => 'authorization',
        ]);

        $orderActionLogDataHandler = $this->createMock(OrderActionLogDataHandlerInterface::class);
        $orderActionLogDataHandler->expects(static::once())->method('createOrderActionLog');

        return new PayonePaypalV2PaymentHandler(
            $configReader,
            $this->createMock(EntityRepository::class),
            $this->getRequestStack($dataBag),
            $client,
            $translator,
            new TransactionDataHandler($this->createMock(EntityRepository::class), new CurrencyPrecision()),
            $orderActionLogDataHandler,
            new PaymentStateHandler($translator),
            $requestFactory,
            $this->createMock(CustomerDataPersistor::class)
        );
    }

    private function performPayment(
        PayonePaypalV2PaymentHandler $paymentHandler,
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
