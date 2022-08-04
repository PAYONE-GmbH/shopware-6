<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;

class ProfileRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsCorrectProfileParameters(): void
    {
        $struct = new RatepayProfileStruct(
            88880103,
            'EUR',
            Defaults::SALES_CHANNEL,
            PayoneRatepayDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
        );

        $builder    = $this->getContainer()->get(ProfileRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'              => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype'         => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'        => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                'add_paydata[action]'  => 'profile',
                'add_paydata[shop_id]' => 88880103,
                'currency'             => 'EUR',
            ],
            $parameters
        );
    }
}
