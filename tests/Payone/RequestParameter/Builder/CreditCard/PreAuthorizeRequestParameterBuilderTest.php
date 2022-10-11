<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\CreditCard\PreAuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'pseudoCardPan' => 'my-pan',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder    = $this->getContainer()->get($this->getParameterBuilder());
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'clearingtype'  => AbstractRequestParameterBuilder::CLEARING_TYPE_CREDIT_CARD,
                'request'       => $this->getValidRequestAction(),
                'pseudocardpan' => 'my-pan',
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
        return PayoneCreditCardPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
