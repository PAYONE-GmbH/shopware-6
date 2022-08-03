<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Test\TestCaseBase\ConfigurationHelper;
use PayonePayment\Test\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);

        $dataBag = new RequestDataBag([
            'ratepayIban'     => 'DE81500105177147426471',
            'ratepayPhone'    => '0123456789',
            'ratepayBirthday' => '2000-01-01',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayDebitPaymentHandler::class);
        $builder    = $this->getContainer()->get(PreAuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => PreAuthorizeRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
                'clearingtype'                               => PreAuthorizeRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                'iban'                                       => 'DE81500105177147426471',
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
