<?php

declare(strict_types=1);

namespace PayonePayment\Components\ConfigReader;

use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @covers \PayonePayment\Components\ConfigReader\ConfigReader
 */
class ConfigReaderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsCorrectConfigKeyByPaymentHandler(): void
    {
        static::assertSame(
            'PayonePayment.settings.ratepayDebitProfiles',
            ConfigReader::getConfigKeyByPaymentHandler(
                PayoneRatepayDebitPaymentHandler::class,
                'Profiles'
            )
        );
    }

    public function testItReturnsCorrectConfiguration(): void
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects($this->once())->method('getDomain')->willReturn([
            'PayonePayment.settings.myTestConfig' => 'the-value',
        ]);

        $configReader  = new ConfigReader($systemConfigService);
        $configuration = $configReader->read();

        static::assertSame('the-value', $configuration->get('myTestConfig'));
    }
}
