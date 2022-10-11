<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SofortBanking;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\SofortBanking\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'clearingtype'           => AbstractRequestParameterBuilder::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
                'request'                => $this->getValidRequestAction(),
                'onlinebanktransfertype' => 'PNT',
                'bankcountry'            => 'DE',
            ],
            $parameters
        );
    }

    protected function getParameterBuilder(): string
    {
        return AuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayoneSofortBankingPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
