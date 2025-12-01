<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Controller;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\Provider\Ratepay\Service\ProfileService;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class RatepayProfileController extends AbstractController
{
    /**
     * @var array<int, PaymentHandlerInterface>
     */
    private array $paymentHandlers;

    /**
     * @var array<class-string, RequestParameterEnricherChain>
     */
    private array $requestEnricherChains;

    public function __construct(
        private readonly ProfileService $profileService,
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
    }

    #[Route(path: '/api/_action/payone/reload-ratepay-profiles', name: 'api.action.payone.reload_ratepay_profiles', methods: ['POST'])]
    public function reloadProfiles(Request $request): JsonResponse
    {
        $salesChannelId = $request->request->get('salesChannelId');

        if (null !== $salesChannelId && !is_string($salesChannelId)) {
            $salesChannelId = (string) $salesChannelId;
        }

        $scIdParam = ('null' === $salesChannelId || null === $salesChannelId) ? null : $salesChannelId;

        $results = [];

        foreach ($this->paymentHandlers as $paymentHandler) {
            $paymentHandlerClassName = $paymentHandler::class;

            /** @var array{updates?: array, errors?: array} $result */
            $result = $this->profileService->updateProfileConfiguration(
                $paymentHandler,
                $this->requestEnricherChains[$paymentHandlerClassName],
                $scIdParam,
            );

            $key             = $salesChannelId ?? '';
            $results[$key][] = $result;
        }

        $finalData   = [];
        $responseKey = $scIdParam ?? 'null';

        foreach ($results as $updateResultsBySalesChannel) {
            $salesChannelData = [
                'updates' => [],
                'errors'  => [],
            ];

            /** @var array{updates?: array, errors?: array} $updateResult */
            foreach ($updateResultsBySalesChannel as $updateResult) {
                if (isset($updateResult['updates']) && is_array($updateResult['updates'])) {
                    $salesChannelData['updates'][] = $updateResult['updates'];
                }

                if (isset($updateResult['errors']) && is_array($updateResult['errors'])) {
                    $salesChannelData['errors'][] = $updateResult['errors'];
                }
            }

            $salesChannelData['updates'] = count($salesChannelData['updates']) > 0
                ? array_merge(...$salesChannelData['updates'])
                : [];

            $salesChannelData['errors'] = count($salesChannelData['errors']) > 0
                ? array_merge(...$salesChannelData['errors'])
                : [];

            $finalData['payoneRatepayProfilesUpdateResult'][$responseKey] = $salesChannelData;
        }

        return new JsonResponse($finalData);
    }
}
