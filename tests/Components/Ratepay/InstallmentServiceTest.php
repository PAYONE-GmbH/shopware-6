<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment\CalculationRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Components\Ratepay\InstallmentService
 */
class InstallmentServiceTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItReturnsDefaultCalculatorData(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'interestrate-min' => '10.7',
                'interestrate-max' => '100.9',
                'month-allowed'    => '1,2,3',
            ]
        );

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $installmentService  = $this->getInstallmentService();
        $this->fillCart($salesChannelContext->getToken(), 100);

        $calculatorData = $installmentService->getInstallmentCalculatorData($salesChannelContext);

        static::assertNotNull($calculatorData);
        static::assertSame(
            CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME,
            $calculatorData->getCalculationParams()['ratepayInstallmentType']
        );
        static::assertSame(
            '1', // First month of the profile's allowed months
            $calculatorData->getCalculationParams()['ratepayInstallmentValue']
        );
        static::assertSame(10.7, $calculatorData->getMinimumRate());
        static::assertSame(100.9, $calculatorData->getMaximumRate());
        static::assertArrayHasKey('addpaydata', $calculatorData->getCalculationResponse());
    }

    public function testItReturnsCalculatorDataByTime(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'interestrate-min' => '10.7',
                'interestrate-max' => '100.9',
                'month-allowed'    => '1,2,3',
            ]
        );

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $installmentService  = $this->getInstallmentService();
        $this->fillCart($salesChannelContext->getToken(), 100);

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME,
            'ratepayInstallmentValue' => 3,
        ]);

        $calculatorData = $installmentService->getInstallmentCalculatorData($salesChannelContext, $dataBag);

        static::assertNotNull($calculatorData);
        static::assertSame(
            CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME,
            $calculatorData->getCalculationParams()['ratepayInstallmentType']
        );
        static::assertSame(
            3,
            $calculatorData->getCalculationParams()['ratepayInstallmentValue']
        );
        static::assertSame(10.7, $calculatorData->getMinimumRate());
        static::assertSame(100.9, $calculatorData->getMaximumRate());
        static::assertArrayHasKey('addpaydata', $calculatorData->getCalculationResponse());
    }

    public function testItReturnsCalculatorDataByRate(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'interestrate-min' => '10.7',
                'interestrate-max' => '100.9',
                'month-allowed'    => '1,2,3',
            ]
        );

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $installmentService  = $this->getInstallmentService();
        $this->fillCart($salesChannelContext->getToken(), 100);

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE,
            'ratepayInstallmentValue' => 20,
        ]);

        $calculatorData = $installmentService->getInstallmentCalculatorData($salesChannelContext, $dataBag);

        static::assertNotNull($calculatorData);
        static::assertSame(
            CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE,
            $calculatorData->getCalculationParams()['ratepayInstallmentType']
        );
        static::assertSame(
            20,
            $calculatorData->getCalculationParams()['ratepayInstallmentValue']
        );
        static::assertSame(10.7, $calculatorData->getMinimumRate());
        static::assertSame(100.9, $calculatorData->getMaximumRate());
        static::assertArrayHasKey('addpaydata', $calculatorData->getCalculationResponse());
    }

    protected function getInstallmentService(): InstallmentService
    {
        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn($this->getCalculationResponse());

        return new InstallmentService(
            $this->getContainer()->get(CartService::class),
            $client,
            $this->getContainer()->get(RequestParameterFactory::class),
            $this->getContainer()->get(ProfileService::class)
        );
    }

    protected function getCalculationResponse(): array
    {
        return [
            'addpaydata' => [
                'annual-percentage-rate' => '14.02',
                'interest-amount'        => '19.76',
                'amount'                 => '1581.32',
                'number-of-rates'        => '3',
                'rate'                   => '533.7',
                'payment-firstday'       => '2',
                'interest-rate'          => '13.7',
                'monthly-debit-interest' => '1.08',
                'last-rate'              => '533.68',
                'service-charge'         => '0',
                'total-amount'           => '1601.08',
            ],
            'status'      => 'OK',
            'workorderid' => 'WX1A1VEYXLLESEZD',
        ];
    }
}
