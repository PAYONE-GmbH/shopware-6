<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\DeviceFingerprint\RatepayDeviceFingerprintService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler
 */
class PayoneRatepayDebitPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentWithAuthorizationAndSavesCorrectTransactionData(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                'add_paydata[shop_id]' => '88880103',
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'APPROVED',
                'txid' => '123456789',
                'userid' => '987654321',
                'clearing' => [
                    'Reference' => 'DN123',
                ],
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
                        'transactionState' => 'APPROVED',
                        'workOrderId' => null,
                        'clearingReference' => 'DN123',
                        'captureMode' => 'completed',
                        'clearingType' => 'fnc',
                        'financingType' => 'RPD',
                        'additionalData' => ['used_ratepay_shop_id' => '88880103'],
                    ],
                    $transactionData
                );

                static::assertArrayHasKey('request', array_values($transactionData['transactionData'])[0]);
                static::assertArrayHasKey('response', array_values($transactionData['transactionData'])[0]);

                return true;
            })
        );

        $deviceFingerprintService = $this->createMock(RatepayDeviceFingerprintService::class);
        $deviceFingerprintService->expects(static::once())->method('deleteDeviceIdentToken');

        $dataBag = new RequestDataBag([]);
        $paymentHandler = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $requestFactory,
            $deviceFingerprintService,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneRatepayDebitPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentWithPreAuthorizationAndSavesCorrectTransactionData(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                'add_paydata[shop_id]' => '88880103',
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'APPROVED',
                'txid' => '123456789',
                'userid' => '987654321',
                'addpaydata' => [
                    'workorderid' => 'ABC123',
                    'reservation_txid' => '123ABC',
                    'clearing_reference' => 'DN123',
                ],
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
                        'transactionState' => 'APPROVED',
                        'workOrderId' => null,
                        'clearingReference' => 'DN123',
                        'captureMode' => 'completed',
                        'clearingType' => 'fnc',
                        'financingType' => 'RPD',
                        'additionalData' => ['used_ratepay_shop_id' => '88880103'],
                    ],
                    $transactionData
                );

                static::assertArrayHasKey('request', array_values($transactionData['transactionData'])[0]);
                static::assertArrayHasKey('response', array_values($transactionData['transactionData'])[0]);

                return true;
            })
        );

        $deviceFingerprintService = $this->createMock(RatepayDeviceFingerprintService::class);
        $deviceFingerprintService->expects(static::once())->method('deleteDeviceIdentToken');

        $dataBag = new RequestDataBag([]);
        $paymentHandler = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $requestFactory,
            $deviceFingerprintService,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneRatepayDebitPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        RequestParameterFactory $requestFactory,
        RatepayDeviceFingerprintService $deviceFingerprintService,
        RequestDataBag $dataBag
    ): PayoneRatepayDebitPaymentHandler {
        return new PayoneRatepayDebitPaymentHandler(
            $this->getContainer()->get(ConfigReader::class),
            $client,
            $this->getContainer()->get('translator'),
            $dataHandler,
            $this->getContainer()->get('order_line_item.repository'),
            $this->getRequestStack($dataBag),
            $requestFactory,
            $deviceFingerprintService
        );
    }

    private function performPayment(
        AbstractPayonePaymentHandler $paymentHandler,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        $paymentHandler->pay(
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
