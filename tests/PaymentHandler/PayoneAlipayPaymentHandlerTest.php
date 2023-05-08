<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler
 */
class PayoneAlipayPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentWithAuthorizationAndSavesCorrectTransactionData(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_WALLET,
                'wallettype' => 'ALP',
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'REDIRECT',
                'txid' => '123456789',
                'userid' => '987654321',
                'redirecturl' => 'the-redirect-url',
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects(static::once())->method('saveTransactionData')->with(
            static::anything(),
            static::anything(),
            static::callback(static function ($transactionData) {
                Assert::assertArraySubset(
                    [
                        'authorizationType' => 'authorization',
                        'lastRequest' => 'authorization',
                        'transactionId' => '123456789',
                        'sequenceNumber' => -1,
                        'userId' => '987654321',
                        'transactionState' => 'REDIRECT',
                        'allowCapture' => false,
                        'allowRefund' => false,
                    ],
                    $transactionData
                );

                static::assertArrayHasKey('request', array_values($transactionData['transactionData'])[0]);
                static::assertArrayHasKey('response', array_values($transactionData['transactionData'])[0]);

                return true;
            })
        );

        $stateHandler = $this->createMock(PaymentStateHandlerInterface::class);

        $dataBag = new RequestDataBag([]);
        $paymentHandler = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $stateHandler,
            $requestFactory,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneAlipayPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('the-redirect-url', $response->getTargetUrl());
    }

    public function testItPerformsPaymentWithPreAuthorizationAndSavesCorrectTransactionData(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_WALLET,
                'wallettype' => 'ALP',
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'REDIRECT',
                'txid' => '123456789',
                'userid' => '987654321',
                'redirecturl' => 'the-redirect-url',
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects(static::once())->method('saveTransactionData')->with(
            static::anything(),
            static::anything(),
            static::callback(static function ($transactionData) {
                Assert::assertArraySubset(
                    [
                        'authorizationType' => 'preauthorization',
                        'lastRequest' => 'preauthorization',
                        'transactionId' => '123456789',
                        'sequenceNumber' => -1,
                        'userId' => '987654321',
                        'transactionState' => 'REDIRECT',
                        'allowCapture' => false,
                        'allowRefund' => false,
                    ],
                    $transactionData
                );

                static::assertArrayHasKey('request', array_values($transactionData['transactionData'])[0]);
                static::assertArrayHasKey('response', array_values($transactionData['transactionData'])[0]);

                return true;
            })
        );

        $stateHandler = $this->createMock(PaymentStateHandlerInterface::class);

        $dataBag = new RequestDataBag([]);
        $paymentHandler = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $stateHandler,
            $requestFactory,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneAlipayPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('the-redirect-url', $response->getTargetUrl());
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestParameterFactory $requestFactory,
        RequestDataBag $dataBag
    ): PayoneAlipayPaymentHandler {
        return new PayoneAlipayPaymentHandler(
            $this->getContainer()->get(ConfigReader::class),
            $this->getContainer()->get('order_line_item.repository'),
            $this->getRequestStack($dataBag),
            $client,
            $this->getContainer()->get('translator'),
            $dataHandler,
            $stateHandler,
            $requestFactory
        );
    }

    private function performPayment(
        AbstractPayonePaymentHandler $paymentHandler,
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
