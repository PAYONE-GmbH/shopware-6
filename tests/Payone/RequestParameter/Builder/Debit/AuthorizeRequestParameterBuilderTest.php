<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Debit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Debit\AuthorizeRequestParameterBuilder
 */
class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'iban'         => 'DE61500105178278794285',
            'bic'          => 'Test123',
            'accountOwner' => 'Max Mustermann',
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
                'clearingtype'      => AbstractRequestParameterBuilder::CLEARING_TYPE_DEBIT,
                'request'           => $this->getValidRequestAction(),
                'iban'              => 'DE61500105178278794285',
                'bic'               => 'Test123',
                'bankaccountholder' => 'Max Mustermann',
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
        return PayoneDebitPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
