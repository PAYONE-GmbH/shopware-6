<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

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

class PayonePaypalPaymentHandlerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItPerformsPaymentAndReturnsCorrectRedirectUrl(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag             = new RequestDataBag();

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'status' => 'test-status',
                'txid'   => '',
                'userid' => '',
            ]
        );

        $requestFactory = $this->createMock(RequestParameterFactory::class);
        $requestFactory->expects($this->once())->method('getRequestParameter')->willReturn(
            [
                'request'    => '',
                'successurl' => 'test-url',
            ]
        );

        $paymentHandler     = $this->getPaymentHandler($client, $dataBag, $requestFactory);
        $paymentTransaction = $this->getPaymentTransaction(
            $this->getRandomOrder($salesChannelContext),
            PayonePaypalPaymentHandler::class
        );

        $response = $this->performPayment($paymentHandler, $paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('test-url', $response->getTargetUrl());
    }

    private function getPaymentHandler(
        PayoneClientInterface $client,
        RequestDataBag $dataBag,
        RequestParameterFactory $requestFactory
    ): PayonePaypalPaymentHandler {
        $translator   = $this->getContainer()->get('translator');
        $configReader = new ConfigReaderMock([
            'paypalAuthorizationMethod' => 'authorization',
        ]);

        return new PayonePaypalPaymentHandler(
            $configReader,
            $client,
            $translator,
            new TransactionDataHandler($this->createMock(EntityRepositoryInterface::class), new CurrencyPrecision()),
            $this->createMock(EntityRepositoryInterface::class),
            new PaymentStateHandler($translator),
            $this->getRequestStack($dataBag),
            $requestFactory
        );
    }

    private function performPayment(
        PayonePaypalPaymentHandler $paymentHandler,
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
