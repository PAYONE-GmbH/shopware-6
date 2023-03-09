<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\DeviceFingerprint\PayoneBNPLDeviceFingerprintService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler
 */
class PayoneSecuredInvoicePaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentWithAuthorizationAndSavesCorrectTransactionData(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects(static::once())->method('getRequestParameter')->willReturn([
            'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(
            [
                'status' => 'APPROVED',
                'txid' => '123456789',
                'userid' => '987654321',
                'clearing' => [
                    'BankAccount' => [
                        'Key' => 'Value',
                        'Reference' => 'DN123',
                    ],
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
                        'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
                    ],
                    $transactionData
                );

                Assert::assertArraySubset(
                    [
                        'Key' => 'Value',
                        'Reference' => '123456789',
                    ],
                    $transactionData['clearingBankAccount']
                );

                return true;
            })
        );

        $deviceFingerprintService = $this->createMock(PayoneBNPLDeviceFingerprintService::class);
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
            PayoneSecuredInvoicePaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        RequestParameterFactory $requestFactory,
        PayoneBNPLDeviceFingerprintService $deviceFingerprintService,
        RequestDataBag $dataBag
    ): PayoneSecuredInvoicePaymentHandler {
        return new PayoneSecuredInvoicePaymentHandler(
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
            new SyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder(),
                ''
            ),
            $dataBag,
            $salesChannelContext
        );
    }
}
