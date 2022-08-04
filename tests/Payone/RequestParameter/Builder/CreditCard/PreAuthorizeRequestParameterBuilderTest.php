<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\CreditCard;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class PreAuthorizeRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsCorrectPreAuthorizeParameters(): void
    {
        $dataBag = new RequestDataBag([
            'pseudoCardPan' => 'my-pan',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneCreditCardPaymentHandler::class);
        $builder    = $this->getContainer()->get(PreAuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'clearingtype'  => AbstractRequestParameterBuilder::CLEARING_TYPE_CREDIT_CARD,
                'request'       => AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE,
                'pseudocardpan' => 'my-pan',
            ],
            $parameters
        );
    }
}
