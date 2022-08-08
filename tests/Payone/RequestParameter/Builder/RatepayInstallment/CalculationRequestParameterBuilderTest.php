<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Ratepay\ProfileService;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment\CalculationRequestParameterBuilder
 */
class CalculationRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItSupportsValidCalculationRequest(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayInstallmentPaymentHandler::class);

        $struct  = $this->getRatepayCalculationStruct(CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE, 10);
        $builder = $this->getContainer()->get(CalculationRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsInvalidRequestAction(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayInstallmentPaymentHandler::class);

        $struct = $this->getRatepayCalculationStruct(
            CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE,
            10,
            PayoneRatepayInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
        );

        $builder = $this->getContainer()->get(CalculationRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsInvalidPaymentMethod(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);

        $struct = $this->getRatepayCalculationStruct(
            CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE,
            10,
            PayoneRatepayDebitPaymentHandler::class,
        );

        $builder = $this->getContainer()->get(CalculationRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            PayoneRatepayInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(CalculationRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsTheInstallmentCalculationRequestParametersByRate(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayInstallmentPaymentHandler::class);

        $struct     = $this->getRatepayCalculationStruct(CalculationRequestParameterBuilder::INSTALLMENT_TYPE_RATE, 10);
        $builder    = $this->getContainer()->get(CalculationRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => 'genericpayment',
                'clearingtype'                               => CalculationRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'add_paydata[action]'                        => 'calculation',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[calculation_type]'              => 'calculation-by-rate',
                'add_paydata[rate]'                          => 10,
                'amount'                                     => 10000,
                'currency'                                   => 'EUR',
            ],
            $parameters
        );
    }

    public function testItAddsTheInstallmentCalculationRequestParametersByTime(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayInstallmentPaymentHandler::class);

        $struct     = $this->getRatepayCalculationStruct(CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME, 10);
        $builder    = $this->getContainer()->get(CalculationRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => 'genericpayment',
                'clearingtype'                               => CalculationRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'add_paydata[action]'                        => 'calculation',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[calculation_type]'              => 'calculation-by-time',
                'add_paydata[month]'                         => 10,
                'amount'                                     => 10000,
                'currency'                                   => 'EUR',
            ],
            $parameters
        );
    }

    protected function getRatepayCalculationStruct(
        string $installmentType,
        int $installmentValue,
        string $paymentHandler = PayoneRatepayInstallmentPaymentHandler::class,
        string $requestAction = AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
    ): RatepayCalculationStruct {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $cart                = $this->fillCart($salesChannelContext->getToken(), 100);
        $profile             = $this->getContainer()->get(ProfileService::class)->getProfileBySalesChannelContext(
            $salesChannelContext,
            $paymentHandler
        );

        $dataBag = new RequestDataBag([
            'ratepayInstallmentType'  => $installmentType,
            'ratepayInstallmentValue' => $installmentValue,
        ]);

        return new RatepayCalculationStruct(
            $cart,
            $dataBag,
            $salesChannelContext,
            $profile,
            $paymentHandler,
            $requestAction
        );
    }
}
