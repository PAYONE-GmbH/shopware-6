<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\Profile\ProfileServiceInterface;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Kernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \PayonePayment\EventListener\KernelEventListener
 */
class KernelEventListenerTest extends TestCase
{
    use ConfigurationHelper;

    public function testItInitializesUpdateOfProfileConfigurations(): void
    {
        $event = new ResponseEvent(
            $this->createMock(Kernel::class),
            $this->getValidRequest(),
            defined(HttpKernelInterface::class . '::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $profileService = $this->createMock(ProfileServiceInterface::class);
        $profileService->expects($this->once())->method('updateProfileConfiguration')->with(
            $this->equalTo(PayoneRatepayDebitPaymentHandler::class),
            $this->isNull()
        );

        $listener = new KernelEventListener($profileService);
        $listener->onKernelResponse($event);
    }

    public function testItInitializesUpdateOfProfileConfigurationsAndUpdatesJsonResponse(): void
    {
        $response = new JsonResponse([
            'the-key' => 'the-value',
        ]);

        $event = new ResponseEvent(
            $this->createMock(Kernel::class),
            $this->getValidRequest(),
            defined(HttpKernelInterface::class . '::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $updates = [
            'PayonePayment.settings.ratepayDebitProfiles'              => $this->getValidRatepayProfiles(),
            'PayonePayment.settings.ratepayDebitProfileConfigurations' => $this->getValidRatepayProfileConfigurations(),
        ];

        $profileService = $this->createMock(ProfileServiceInterface::class);
        $profileService->expects($this->once())->method('updateProfileConfiguration')->with(
            $this->equalTo(PayoneRatepayDebitPaymentHandler::class),
            $this->isNull()
        )->willReturn([
            'updates' => $updates,
            'errors'  => [],
        ]);

        $listener = new KernelEventListener($profileService);
        $listener->onKernelResponse($event);

        $responseData = json_decode($response->getContent(), true);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('the-value', $responseData['the-key']);
        static::assertArrayHasKey('payoneRatepayProfilesUpdateResult', $responseData);
        static::assertArrayHasKey('null', $responseData['payoneRatepayProfilesUpdateResult']);
        static::assertSame($updates, $responseData['payoneRatepayProfilesUpdateResult']['null']['updates']);
    }

    public function testItNotInitializesUpdateOfProfileConfigurationsOnWrongRoute(): void
    {
        $event = new ResponseEvent(
            $this->createMock(Kernel::class),
            $this->getRequestWithWrongRoute(),
            defined(HttpKernelInterface::class . '::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $profileService = $this->createMock(ProfileServiceInterface::class);
        $profileService->expects($this->never())->method('updateProfileConfiguration');

        $listener = new KernelEventListener($profileService);
        $listener->onKernelResponse($event);
    }

    public function testItNotInitializesUpdateOfProfileConfigurationsOnMissingProfilesConfiguration(): void
    {
        $event = new ResponseEvent(
            $this->createMock(Kernel::class),
            $this->getRequestWithMissingConfigurations(),
            defined(HttpKernelInterface::class . '::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $profileService = $this->createMock(ProfileServiceInterface::class);
        $profileService->expects($this->never())->method('updateProfileConfiguration');

        $listener = new KernelEventListener($profileService);
        $listener->onKernelResponse($event);
    }

    protected function getValidRequest(): Request
    {
        return new Request(
            [
                '_route' => 'api.action.core.save.system-config.batch',
            ],
            [
                'null' => [
                    'PayonePayment.settings.ratepayDebitProfiles' => $this->getValidRatepayProfiles(),
                ],
            ]
        );
    }

    protected function getRequestWithWrongRoute(): Request
    {
        return new Request(
            [
                '_route' => 'frontend.home.page',
            ],
            [
                'null' => [
                    'PayonePayment.settings.ratepayDebitProfiles' => $this->getValidRatepayProfiles(),
                ],
            ]
        );
    }

    protected function getRequestWithMissingConfigurations(): Request
    {
        return new Request(
            [
                '_route' => 'api.action.core.save.system-config.batch',
            ],
            [
                'null' => [],
            ]
        );
    }
}
