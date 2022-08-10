<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\Ratepay\DeviceFingerprint\DeviceFingerprintService;
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
 * @covers \PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler
 */
class PayoneRatepayInstallmentPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentWithAuthorizationAndSavesCorrectTransactionCustomFields(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'              => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'clearingtype'         => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'        => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'add_paydata[shop_id]' => 88880103,
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status'   => 'APPROVED',
                'txid'     => '123456789',
                'userid'   => '987654321',
                'clearing' => [
                    'Reference' => 'DN123',
                ],
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects($this->once())->method('saveTransactionData')->with(
            $this->anything(),
            $this->anything(),
            $this->equalTo([
                'payone_authorization_type'   => 'authorization',
                'payone_last_request'         => 'authorization',
                'payone_transaction_id'       => '123456789',
                'payone_sequence_number'      => -1,
                'payone_user_id'              => '987654321',
                'payone_transaction_state'    => 'APPROVED',
                'payone_allow_capture'        => false,
                'payone_captured_amount'      => 0,
                'payone_allow_refund'         => false,
                'payone_refunded_amount'      => 0,
                'payone_work_order_id'        => null,
                'payone_clearing_reference'   => 'DN123',
                'payone_capture_mode'         => 'completed',
                'payone_clearing_type'        => 'fnc',
                'payone_financing_type'       => 'RPS',
                'payone_used_ratepay_shop_id' => 88880103,
            ])
        );

        $deviceFingerprintService = $this->createMock(DeviceFingerprintService::class);
        $deviceFingerprintService->expects($this->once())->method('deleteDeviceIdentToken');

        $dataBag            = new RequestDataBag([]);
        $paymentHandler     = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $requestFactory,
            $deviceFingerprintService,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneRatepayInstallmentPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testItPerformsPaymentWithPreAuthorizationAndSavesCorrectTransactionCustomFields(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'              => AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
                'clearingtype'         => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'        => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'add_paydata[shop_id]' => 88880103,
            ]
        );

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status'     => 'APPROVED',
                'txid'       => '123456789',
                'userid'     => '987654321',
                'addpaydata' => [
                    'workorderid'        => 'ABC123',
                    'reservation_txid'   => '123ABC',
                    'clearing_reference' => 'DN123',
                ],
            ]
        );

        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);
        $dataHandler->expects($this->once())->method('saveTransactionData')->with(
            $this->anything(),
            $this->anything(),
            $this->equalTo([
                'payone_authorization_type'   => 'preauthorization',
                'payone_last_request'         => 'preauthorization',
                'payone_transaction_id'       => '123456789',
                'payone_sequence_number'      => -1,
                'payone_user_id'              => '987654321',
                'payone_transaction_state'    => 'APPROVED',
                'payone_allow_capture'        => false,
                'payone_captured_amount'      => 0,
                'payone_allow_refund'         => false,
                'payone_refunded_amount'      => 0,
                'payone_work_order_id'        => null,
                'payone_clearing_reference'   => 'DN123',
                'payone_capture_mode'         => 'completed',
                'payone_clearing_type'        => 'fnc',
                'payone_financing_type'       => 'RPS',
                'payone_used_ratepay_shop_id' => 88880103,
            ])
        );

        $deviceFingerprintService = $this->createMock(DeviceFingerprintService::class);
        $deviceFingerprintService->expects($this->once())->method('deleteDeviceIdentToken');

        $dataBag            = new RequestDataBag([]);
        $paymentHandler     = $this->getPaymentHandler(
            $client,
            $dataHandler,
            $requestFactory,
            $deviceFingerprintService,
            $dataBag
        );
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneRatepayInstallmentPaymentHandler::class
        );

        $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        RequestParameterFactory $requestFactory,
        DeviceFingerprintService $deviceFingerprintService,
        RequestDataBag $dataBag
    ): PayoneRatepayInstallmentPaymentHandler {
        return new PayoneRatepayInstallmentPaymentHandler(
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
