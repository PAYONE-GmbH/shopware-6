<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Ratepay\Profile\ProfileServiceInterface;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class KernelEventListener implements EventSubscriberInterface
{
    public function __construct(private readonly ProfileServiceInterface $profileService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $route = $event->getRequest()->get('_route');
        $response = $event->getResponse();

        if ($route === 'api.action.core.save.system-config.batch'
            && ($response->getStatusCode() === Response::HTTP_OK || $response->getStatusCode() === Response::HTTP_NO_CONTENT)
        ) {
            $results = [];
            $configurations = $event->getRequest()->request->all();

            foreach ($configurations as $salesChannelId => $configuration) {
                foreach (PaymentHandlerGroups::RATEPAY as $ratepayHandler) {
                    $profilesConfigKey = ConfigReader::getConfigKeyByPaymentHandler($ratepayHandler, 'Profiles');

                    if (isset($configuration[$profilesConfigKey])) {
                        $result = $this->profileService->updateProfileConfiguration(
                            $ratepayHandler,
                            $salesChannelId === 'null' ? null : (string) $salesChannelId
                        );
                        $results[$salesChannelId][] = $result;
                    }
                }
            }

            if ($response instanceof JsonResponse && \count($results) > 0) {
                $this->setResponseData($response, $results);
            }
        }
    }

    protected function setResponseData(JsonResponse $response, array $updateResults): void
    {
        $data = [];

        if ($response->getContent()) {
            $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        }

        $data['payoneRatepayProfilesUpdateResult'] = [];
        foreach ($updateResults as $salesChannelId => $updateResultsBySalesChannel) {
            $salesChannelData = [
                'updates' => [],
                'errors' => [],
            ];

            foreach ($updateResultsBySalesChannel as $updateResult) {
                $salesChannelData['updates'][] = $updateResult['updates'];
                $salesChannelData['errors'][] = $updateResult['errors'];
            }

            $salesChannelData['updates'] = array_merge(...$salesChannelData['updates']);
            $salesChannelData['errors'] = array_merge(...$salesChannelData['errors']);

            $data['payoneRatepayProfilesUpdateResult'][$salesChannelId] = $salesChannelData;
        }

        $response->setData($data);
    }
}
