<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Ratepay;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\TestDefaults;

class RatepayDebitAuthorizationRequestFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRatepayAuthorizationProfileParameters(): void
    {
//        $context             = Context::createDefaultContext();
//        $salesChannelContext = Generator::createSalesChannelContext($context);
//        $factory             = $this->getContainer()->get(RequestParameterFactory::class);
//
//        $dataBag             = new RequestDataBag([
//            'iban'         => '',
//            'bic'          => '',
//            'accountOwner' => '',
//        ]);
//
//        $request = $factory->getRequestParameter(
//            new PaymentTransactionStruct(
//
//            )
//        );
//
//        Assert::assertArraySubset(
//            [
//                'request'              => 'genericpayment',
//                'clearingtype'         => 'fnc',
//                'financingtype'        => 'RPD',
//                'add_paydata[action]'  => 'profile',
//                'add_paydata[shop_id]' => 88880103,
//                'currency'             => 'EUR',
//            ],
//            $request
//        );
    }
}
