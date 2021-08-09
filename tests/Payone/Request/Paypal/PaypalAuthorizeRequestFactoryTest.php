<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Paypal;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Test\Mock\Factory\RequestParameterFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PaypalAuthorizeRequestFactoryTest extends TestCase
{
    use RequestParameterFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $factory = $this->getRequestParameterFactory($salesChannelContext);

        $request = $factory->getRequestParameter(
            new PaymentTransactionStruct(
                $this->getPaymentTransaction(PayonePaypalPaymentHandler::class),
                new RequestDataBag([]),
                $salesChannelContext,
                PayonePaypalPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
            )
        );

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'city'            => 'Some City',
                'clearingtype'    => 'wlt',
                'currency'        => 'EUR',
                'email'           => 'first.last@example.com',
                'encoding'        => 'UTF-8',
                'firstname'       => 'First',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'language'        => 'de',
                'lastname'        => 'Last',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'authorization',
                'solution_name'   => 'kellerkinder',
                'street'          => 'Some Street 1',
                'wallettype'      => 'PPE',
                'zip'             => '12345',
                'reference'       => '1',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }
}
