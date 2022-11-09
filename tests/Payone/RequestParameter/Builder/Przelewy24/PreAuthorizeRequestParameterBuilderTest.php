<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Przelewy24;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Przelewy24\PreAuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;
    use ConfigurationHelper;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request' => $this->getValidRequestAction(),
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
                'onlinebanktransfertype' => 'P24',
                'bankcountry' => 'PL',
            ],
            $parameters
        );
    }

    protected function getParameterBuilder(): string
    {
        return PreAuthorizeRequestParameterBuilder::class;
    }

    protected function getValidPaymentHandler(): string
    {
        return PayonePrzelewy24PaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
