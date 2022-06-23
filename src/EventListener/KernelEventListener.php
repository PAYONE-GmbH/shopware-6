<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\ProfileService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class KernelEventListener implements EventSubscriberInterface
{
    /** @var ProfileService */
    private $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
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
        if ($route === 'api.action.core.save.system-config.batch') {
            $results = [];
            $configurations = $event->getRequest()->request->all();

            foreach ($configurations as $salesChannelId => $configuration) {
                foreach (ProfileService::PROFILES_CONFIG_KEYS as $configKey) {
                    if (isset($configuration[$configKey])) {
                        $result = $this->profileService->updateProfileConfiguration(
                            $configKey,
                            $salesChannelId === 'null' ? null : $salesChannelId
                        );
                        $results[$salesChannelId][] = $result;
                    }
                }
            }

            if ($response instanceof JsonResponse && count($results) > 0) {
                $this->setResponseData($response, $results);
            }
        }
    }

    protected function setResponseData(JsonResponse $response, array $updateResults): void
    {
        $data = [];
        if ($response->getContent()) {
            $data = json_decode($response->getContent(), true);
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
        $response->setStatusCode(Response::HTTP_OK);
    }
}
