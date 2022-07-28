<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Ratepay;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\TestDefaults;

class RatepayDebitProfileRequestFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRatepayDebitProfileParameters(): void
    {
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $request = $factory->getRequestParameter(
            new RatepayProfileStruct(
                88880103,
                'EUR',
                TestDefaults::SALES_CHANNEL,
                PayoneRatepayDebitPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
            )
        );

        Assert::assertArraySubset(
            [
                'request'              => 'genericpayment',
                'clearingtype'         => 'fnc',
                'financingtype'        => 'RPD',
                'add_paydata[action]'  => 'profile',
                'add_paydata[shop_id]' => 88880103,
                'currency'             => 'EUR',
            ],
            $request
        );
    }
}
