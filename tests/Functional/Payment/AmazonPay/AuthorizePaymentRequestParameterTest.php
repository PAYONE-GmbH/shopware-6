<?php

declare(strict_types=1);

namespace PayonePayment\Functional\Payment\AmazonPay;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\PaymentHandler\PayoneAmazonPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AuthorizePaymentRequestParameterTest extends TestCase
{
    use PayoneTestBehavior;

    /**
     * @dataProvider authorizeActions
     */
    public function testIfSuccessful(string $authMethod): void
    {
        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $dataBag = new RequestDataBag([
            'payonePhone' => '012345789',
        ]);

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            PayoneAmazonPayPaymentHandler::class,
            $authMethod
        );

        $requestParams = $factory->getRequestParameter($struct);

        static::assertArrayHasKey('request', $requestParams);
        static::assertEquals($authMethod, $requestParams['request']);
        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('AMP', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertIsNumeric($requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertIsString($requestParams['currency']);
        static::assertArrayHasKey('reference', $requestParams);
        static::assertIsString($requestParams['reference']);
        static::assertArrayHasKey('successurl', $requestParams);
        static::assertIsString($requestParams['successurl']);
        static::assertArrayHasKey('backurl', $requestParams);
        static::assertIsString($requestParams['backurl']);
        static::assertArrayHasKey('errorurl', $requestParams);
        static::assertIsString($requestParams['errorurl']);
        static::assertArrayHasKey('add_paydata[platform_id]', $requestParams);

        // test only a few customer fields
        static::assertArrayHasKey('email', $requestParams);
        static::assertIsString($requestParams['email']);
        static::assertArrayHasKey('lastname', $requestParams);
        static::assertIsString($requestParams['lastname']);
        static::assertArrayHasKey('shipping_lastname', $requestParams);
        static::assertIsString($requestParams['shipping_lastname']);
    }

    /**
     * @dataProvider authorizeActions
     */
    public function testIfExceptionOnMissingPhoneNumber(string $authMethod): void
    {
        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $dataBag = new RequestDataBag();

        $struct = $this->getPaymentTransactionStruct(
            $dataBag,
            PayoneAmazonPayPaymentHandler::class,
            $authMethod
        );

        $this->expectException(\RuntimeException::class); // bad method - but this is the actual exception, which got thrown.
        $this->expectExceptionMessage('missing phone number');
        $factory->getRequestParameter($struct);
    }

    protected static function authorizeActions(): array
    {
        return [
            [AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE],
            [AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE],
        ];
    }
}
