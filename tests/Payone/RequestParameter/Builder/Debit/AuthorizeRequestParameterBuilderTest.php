<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Debit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Test\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'iban'         => 'DE61500105178278794285',
            'bic'          => 'Test123',
            'accountOwner' => 'Max Mustermann',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneDebitPaymentHandler::class);
        $builder    = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'clearingtype'      => AbstractRequestParameterBuilder::CLEARING_TYPE_DEBIT,
                'request'           => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'iban'              => 'DE61500105178278794285',
                'bic'               => 'Test123',
                'bankaccountholder' => 'Max Mustermann',
            ],
            $parameters
        );
    }
}
