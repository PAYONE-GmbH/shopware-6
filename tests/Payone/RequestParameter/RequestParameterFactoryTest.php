<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\GetFileStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PayonePayment\Payone\RequestParameter\RequestParameterFactory
 */
class RequestParameterFactoryTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsCorrectRequestParametersWithKey(): void
    {
        $factory = new RequestParameterFactory([
            $this->getSupportedParameterBuilder([
                'key'   => 'value',
                'key-1' => 'value-1',
                'key-2' => 'value-2',
            ]),
            $this->getSupportedParameterBuilder([
                'key-2' => 'other-value',
                'key-3' => 'value-3',
            ]),
            $this->getNotSupportedParameterBuilder(),
        ]);

        $parameters = $factory->getRequestParameter(new AbstractRequestParameterStruct());

        Assert::assertArraySubset(
            [
                'key'   => hash('md5', 'value'),
                'key-1' => 'value-1',
                'key-2' => 'other-value',
                'key-3' => 'value-3',
            ],
            $parameters
        );
        static::assertArrayHasKey('hash', $parameters);
    }

    public function testItReturnsCorrectRequestParametersWithoutKey(): void
    {
        $factory = new RequestParameterFactory([
            $this->getSupportedParameterBuilder([
                'key-1' => 'value-1',
                'key-2' => 'value-2',
                'key-3' => 'value-3',
            ]),
            $this->getNotSupportedParameterBuilder(),
        ]);

        $parameters = $factory->getRequestParameter(new AbstractRequestParameterStruct());

        Assert::assertArraySubset(
            [
                'key-1' => 'value-1',
                'key-2' => 'value-2',
                'key-3' => 'value-3',
            ],
            $parameters
        );
        static::assertArrayNotHasKey('key', $parameters);
        static::assertArrayNotHasKey('hash', $parameters);
    }

    public function testItThrowsExceptionOnNoValidParameterBuilder(): void
    {
        $factory = new RequestParameterFactory([
            $this->getNotSupportedParameterBuilder(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid request parameter builder found');

        $factory->getRequestParameter(new AbstractRequestParameterStruct());
    }

    public function testItFiltersParametersForGetFileRequest(): void
    {
        $factory = new RequestParameterFactory([
            $this->getSupportedParameterBuilder([
                'aid'   => 123,
                'key'   => 'value',
                'key-1' => 'value-1',
            ]),
        ]);

        $arguments = new GetFileStruct(
            $this->createSalesChannelContext(),
            PayoneDebitPaymentHandler::class,
            'ABC-123'
        );
        $parameters = $factory->getRequestParameter($arguments);

        Assert::assertArraySubset(
            [
                'key'   => hash('md5', 'value'),
                'key-1' => 'value-1',
            ],
            $parameters
        );
        static::assertArrayNotHasKey('aid', $parameters);
        static::assertArrayNotHasKey('hash', $parameters);
    }

    protected function getSupportedParameterBuilder(array $parameters): AbstractRequestParameterBuilder
    {
        $builder = $this->createMock(AbstractRequestParameterBuilder::class);
        $builder->expects($this->once())->method('supports')->willReturn(true);
        $builder->expects($this->once())->method('getRequestParameter')->willReturn($parameters);

        return $builder;
    }

    protected function getNotSupportedParameterBuilder(): AbstractRequestParameterBuilder
    {
        $builder = $this->createMock(AbstractRequestParameterBuilder::class);
        $builder->expects($this->once())->method('supports')->willReturn(false);
        $builder->expects($this->never())->method('getRequestParameter');

        return $builder;
    }
}
