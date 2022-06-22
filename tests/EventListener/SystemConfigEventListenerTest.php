<?php

declare(strict_types=1);

namespace PayonePayment\Test\EventListener;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\EventListener\SystemConfigEventListener;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;

class SystemConfigEventListenerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRatepayProfileConfigurationChange(): void
    {
        $this->markTestSkipped('This test uses the real client, we need to further discuss if this is ok.');

        /** @var SystemConfigEventListener $eventListener */
        $eventListener = $this->getContainer()->get(SystemConfigEventListener::class);
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);

        $event = new BeforeSystemConfigChangedEvent(
            'PayonePayment.settings.ratepayDebitProfiles',
            [
                [
                    'shopId'   => 88880103,
                    'currency' => 'EUR',
                ],
            ],
            TestDefaults::SALES_CHANNEL
        );

        $eventListener->beforeSystemConfigChanged($event);

        $config = $systemConfigService->get(
            'PayonePayment.settings.ratepayDebitProfileConfigurations',
            TestDefaults::SALES_CHANNEL
        );
        Assert::assertArraySubset(
            [
                'currency'      => 'EUR',
                'merchant-name' => 'PAYONE',
            ],
            $config[88880103]
        );
    }
}
