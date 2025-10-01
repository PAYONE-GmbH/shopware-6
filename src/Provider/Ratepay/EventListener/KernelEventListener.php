<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\Provider\Ratepay\Service\ProfileService;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

readonly class KernelEventListener implements EventSubscriberInterface
{
    private array $paymentHandlers;

    private array $requestEnricherChains;

    private Serializer $serializer;

    public function __construct(
        private ProfileService $profileService,
        private ConfigReader $configReader,
        DebitPaymentHandler $debitPaymentHandler,
        RequestParameterEnricherChain $debitRequestEnricherChain,
        InstallmentPaymentHandler $installmentPaymentHandler,
        RequestParameterEnricherChain $installmentRequestEnricherChain,
        InvoicePaymentHandler $invoiceResponseHandler,
        RequestParameterEnricherChain $invoiceRequestEnricherChain,
    ) {
        $this->paymentHandlers = [
            $debitPaymentHandler,
            $installmentPaymentHandler,
            $invoiceResponseHandler,
        ];

        $this->requestEnricherChains = [
            $debitPaymentHandler::class       => $debitRequestEnricherChain,
            $installmentPaymentHandler::class => $installmentRequestEnricherChain,
            $invoiceResponseHandler::class    => $invoiceRequestEnricherChain,
        ];

        $this->serializer = new Serializer(encoders: [ new JsonEncoder() ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $route    = $event->getRequest()->get('_route');
        $response = $event->getResponse();
        $status   = $response->getStatusCode();

        if (
            'api.action.core.save.system-config.batch' !== $route
            || (
                Response::HTTP_OK !== $status
                && Response::HTTP_NO_CONTENT !== $status
            )
        ) {
            return;
        }

        $results        = [];
        $configurations = $event->getRequest()->request->all();

        foreach ($configurations as $salesChannelId => $configuration) {
            foreach ($this->paymentHandlers as $paymentHandler) {
                $paymentHandlerClassName = $paymentHandler::class;

                $profilesConfigKey = $this->configReader->getConfigKeyByPaymentHandler(
                    $paymentHandlerClassName,
                    'Profiles',
                );

                if (isset($configuration[$profilesConfigKey])) {
                    $result = $this->profileService->updateProfileConfiguration(
                        $paymentHandler,
                        $this->requestEnricherChains[$paymentHandler::class],
                        'null' === $salesChannelId ? null : (string) $salesChannelId,
                    );

                    $results[$salesChannelId][] = $result;
                }
            }
        }

        if ($response instanceof JsonResponse && \count($results) > 0) {
            $this->setResponseData($response, $results);
        }
    }

    protected function setResponseData(JsonResponse $response, array $updateResults): void
    {
        $data = [];

        if ($response->getContent()) {
            $data = $this->serializer->decode($response->getContent(), JsonEncoder::FORMAT);
        }

        $data['payoneRatepayProfilesUpdateResult'] = [];
        foreach ($updateResults as $salesChannelId => $updateResultsBySalesChannel) {
            $salesChannelData = [
                'updates' => [],
                'errors'  => [],
            ];

            foreach ($updateResultsBySalesChannel as $updateResult) {
                $salesChannelData['updates'][] = $updateResult['updates'];
                $salesChannelData['errors'][]  = $updateResult['errors'];
            }

            $salesChannelData['updates'] = \array_merge(...$salesChannelData['updates']);
            $salesChannelData['errors']  = \array_merge(...$salesChannelData['errors']);

            $data['payoneRatepayProfilesUpdateResult'][$salesChannelId] = $salesChannelData;
        }

        $response->setData($data);
    }
}
