<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInvoicing;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\Test\TestCaseBase\CheckoutTestBehavior;
use PayonePayment\Test\TestCaseBase\ConfigurationHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use CheckoutTestBehavior;
    use ConfigurationHelper;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($systemConfigService, PayoneRatepayInvoicingPaymentHandler::class);

        $dataBag = new RequestDataBag([
            'ratepayPhone'    => '0123456789',
            'ratepayBirthday' => '2000-01-01',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayInvoicingPaymentHandler::class);
        $builder    = $this->getContainer()->get(PreAuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => PreAuthorizeRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
                'clearingtype'                               => PreAuthorizeRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPV,
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[shop_id]'                       => 88880103,
                'telephonenumber'                            => '0123456789',
                'birthday'                                   => '20000101',
                'it[1]'                                      => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }
}
