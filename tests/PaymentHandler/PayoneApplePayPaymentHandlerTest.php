<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\Currency\CurrencyPrecision;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Payone\Client\PayoneClient;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\Mock\ConfigReaderMock;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\PaymentHandler\PayoneApplePayPaymentHandler
 */
class PayoneApplePayPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfCreateOrderActionLogGotCalled(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $requestDataBag = new RequestDataBag(['response' => json_encode([
            'status' => 'APPROVED',
            'txid' => '123456789',
            'userid' => '987654321',
        ])]);

        /** @var PayoneApplePayPaymentHandler $paymentHandler */
        $paymentHandler = $this->getPaymentHandler($requestDataBag);

        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayoneApplePayPaymentHandler::class
        );

        $paymentHandler->pay(
            new SyncPaymentTransactionStruct(
                $paymentTransaction->getOrderTransaction(),
                $paymentTransaction->getOrder()
            ),
            $requestDataBag,
            $salesChannelContext
        );

        // info: assertion if the `createOrderActionLog` is called, is in the `getPaymentHandler`.
    }

    private function getPaymentHandler(RequestDataBag $dataBag): PayoneApplePayPaymentHandler
    {
        $configReader = new ConfigReaderMock([
            'applePayAuthorizationMethod' => 'authorization',
        ]);

        $orderActionLogDataHandler = $this->createMock(OrderActionLogDataHandlerInterface::class);
        $orderActionLogDataHandler->expects(static::once())->method('createOrderActionLog');

        return new PayoneApplePayPaymentHandler(
            $configReader,
            $this->createMock(EntityRepository::class),
            $this->getRequestStack($dataBag),
            $this->createMock(PayoneClient::class),
            $this->getContainer()->get('translator'),
            new TransactionDataHandler($this->createMock(EntityRepository::class), new CurrencyPrecision()),
            $orderActionLogDataHandler,
            $this->createMock(RequestParameterFactory::class)
        );
    }
}
