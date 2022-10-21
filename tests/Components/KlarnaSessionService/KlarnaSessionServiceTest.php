<?php

declare(strict_types=1);

namespace PayonePayment\Components\KlarnaSessionService;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @covers \PayonePayment\Components\KlarnaSessionService\KlarnaSessionService
 */
class KlarnaSessionServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItCreatesKlarnaSession(): void
    {
        $instance = $this->getInstance($this->getDefaultResponseByApiDoc());

        $sessionStruct = $instance->createKlarnaSession($this->createSalesChannelContext());

        static::assertEquals('my-hash', $sessionStruct->getCartHash());
        static::assertEquals('WX1A37YBGD9D11DK', $sessionStruct->getWorkorderId());
        static::assertEquals('eyJhbGciOiJSUzI...7XYwCCb8rrXYw', $sessionStruct->getClientToken());
        static::assertEquals('pay_over_time', $sessionStruct->getPaymentMethodIdentifier());
    }

    public function testItCreatesKlarnaSessionByOrder(): void
    {
        $instance = $this->getInstance($this->getDefaultResponseByApiDoc());

        $sessionStruct = $instance->createKlarnaSession(
            $this->createSalesChannelContext(),
            $this->getRandomOrder()->getId()
        );

        // basically it is the same test as `testCreateKlarnaSession`. We just make sure that the behaviour is exactly the same.
        static::assertEquals('my-hash', $sessionStruct->getCartHash());
        static::assertEquals('WX1A37YBGD9D11DK', $sessionStruct->getWorkorderId());
        static::assertEquals('eyJhbGciOiJSUzI...7XYwCCb8rrXYw', $sessionStruct->getClientToken());
        static::assertEquals('pay_over_time', $sessionStruct->getPaymentMethodIdentifier());
    }

    private function getInstance(array $expectedResponse): KlarnaSessionService
    {
        $request = $this->createMock(PayoneClientInterface::class);
        $request->method('request')->willReturn($expectedResponse);

        $requestParamFactory = $this->createMock(RequestParameterFactory::class);
        $requestParamFactory->method('getRequestParameter')->willReturn([]);

        $cartService = $this->createMock(CartService::class);

        $cartHasher = $this->createMock(CartHasherInterface::class);
        $cartHasher->method('generate')->willReturn('my-hash');

        $orderRepository = $this->createMock(EntityRepository::class);

        return new KlarnaSessionService(
            $request,
            $requestParamFactory,
            $cartService,
            $cartHasher,
            $orderRepository
        );
    }

    private function getDefaultResponseByApiDoc(): array
    {
        return [
            'status' => 'OK',
            'addpaydata' => [
                'payment_method_category_asset_url_descriptive' => 'https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg',
                'client_token' => 'eyJhbGciOiJSUzI...7XYwCCb8rrXYw',
                'payment_method_category_identifier' => 'pay_over_time',
                'session_id' => '47a35b08-d5ee-71b5-95d1-eae751c5befb',
                'payment_method_category_name' => 'Slice it.',
                'payment_method_category_asset_url_standard' => 'https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg',
            ],
            'workorderid' => 'WX1A37YBGD9D11DK',
        ];
    }
}
