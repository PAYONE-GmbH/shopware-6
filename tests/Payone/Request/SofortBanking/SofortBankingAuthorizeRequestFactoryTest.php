<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\SofortBanking;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Test\Mock\Factory\RequestParameterFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class SofortBankingAuthorizeRequestFactoryTest extends TestCase
{
    use RequestParameterFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $factory = $this->getRequestParameterFactory($salesChannelContext);

        $request = $factory->getRequestParameter(
            new PaymentTransactionStruct(
                $this->getPaymentTransaction(PayoneSofortBankingPaymentHandler::class),
                new RequestDataBag([]),
                $salesChannelContext,
                PayoneSofortBankingPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
            )
        );

        Assert::assertArraySubset(
            [
                'aid'                    => '',
                'amount'                 => 10000,
                'api_version'            => '3.10',
                'bankcountry'            => 'DE',
                'city'                   => 'Some City',
                'clearingtype'           => 'sb',
                'currency'               => 'EUR',
                'email'                  => 'first.last@example.com',
                'encoding'               => 'UTF-8',
                'firstname'              => 'First',
                'integrator_name'        => 'shopware6',
                'key'                    => '',
                'language'               => 'de',
                'lastname'               => 'Last',
                'mid'                    => '',
                'mode'                   => '',
                'onlinebanktransfertype' => 'PNT',
                'portalid'               => '',
                'reference'              => '1',
                'request'                => 'authorization',
                'solution_name'          => 'kellerkinder',
                'street'                 => 'Some Street 1',
                'zip'                    => '12345',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }
}
