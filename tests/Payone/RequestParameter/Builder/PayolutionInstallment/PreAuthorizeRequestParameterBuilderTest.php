<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PaymentTransactionParameterBuilderTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PayolutionInstallment\PreAuthorizeRequestParameterBuilder
 */
class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PaymentTransactionParameterBuilderTestTrait;
    use ConfigurationHelper;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'payolutionIban' => 'DE81500105177147426471',
            'payolutionBic' => 'ABCD1111',
            'payolutionAccountOwner' => 'Tester Test',
            'payolutionInstallmentDuration' => 12,
            RequestConstants::BIRTHDAY => '2000-01-01',
        ]);

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
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYS,
                'add_paydata[installment_duration]' => 12,
                'iban' => 'DE81500105177147426471',
                'bic' => 'ABCD1111',
                'bankaccountholder' => 'Tester Test',
                'birthday' => '20000101',
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
        return PayonePayolutionInstallmentPaymentHandler::class;
    }

    protected function getValidRequestAction(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }
}
