<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Ratepay\ProfileService;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CalculationRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

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

    protected function getRatepayCalculationStruct(string $installmentType, int $installmentValue): RatepayCalculationStruct
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $cart                = $this->fillCart($salesChannelContext->getToken(), 100);
        $profile             = $this->getContainer()->get(ProfileService::class)->getProfileBySalesChannelContext(
            $salesChannelContext,
            PayoneRatepayInstallmentPaymentHandler::class
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
            PayoneRatepayInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
        );
    }
}
