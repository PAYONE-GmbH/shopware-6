<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

trait RatepayProfileParameterBuilderTestTrait
{
    use PayoneTestBehavior;

    public function testItSupportsValidProfileRequest(): void
    {
        $struct = $this->getRatepayProfileStruct($this->getValidPaymentHandler());
        $builder = static::getContainer()->get($this->getParameterBuilder());

        TestCase::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsInvalidRequestAction(): void
    {
        $struct = $this->getRatepayProfileStruct(
            $this->getValidPaymentHandler(),
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = static::getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsInvalidPaymentMethod(): void
    {
        $struct = $this->getRatepayProfileStruct(PayoneDebitPaymentHandler::class);
        $builder = static::getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            $this->getValidPaymentHandler(),
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = static::getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    abstract protected function getParameterBuilder(): string;

    abstract protected function getValidPaymentHandler(): string;
}
