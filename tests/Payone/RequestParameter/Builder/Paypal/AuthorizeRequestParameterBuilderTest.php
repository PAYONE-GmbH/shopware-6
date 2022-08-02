<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Paypal;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Test\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsCorrectAuthorizeParameters(): void
    {
        $dataBag    = new RequestDataBag([]);
        $struct     = $this->getPaymentTransactionStruct($dataBag, PayonePaypalPaymentHandler::class);
        $builder    = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_WALLET,
                'request'      => AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'wallettype'   => 'PPE',
            ],
            $parameters
        );
    }
}
