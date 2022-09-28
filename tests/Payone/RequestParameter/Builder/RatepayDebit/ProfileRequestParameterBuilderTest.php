<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\RatepayProfileParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\RatepayDebit\ProfileRequestParameterBuilder
 */
class ProfileRequestParameterBuilderTest extends TestCase
{
    use RatepayProfileParameterBuilderTestTrait;

    public function testItAddsCorrectProfileParameters(): void
    {
        $struct = $this->getRatepayProfileStruct($this->getValidPaymentHandler());
        $builder = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
                'add_paydata[action]' => 'profile',
                'add_paydata[shop_id]' => '88880103',
                'currency' => 'EUR',
            ],
            $parameters
        );
    }

    protected function getParameterBuilder(): string
    {
        return ProfileRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneRatepayDebitPaymentHandler::class;
    }
}
