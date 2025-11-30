<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Controller;

use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\Provider\Ratepay\Service\ProfileService;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class RatepayProfileController extends AbstractController
{
    private array $paymentHandlers;
    private array $requestEnricherChains;

    public function __construct(
        private readonly ProfileService $profileService,
        private readonly SystemConfigService $systemConfigService,
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
        $scIdParam = ($salesChannelId === 'null' || $salesChannelId === null) ? null : (string) $salesChannelId;

        $this->syncInvoiceToInvoicing($scIdParam);

        $results = [];

        foreach ($this->paymentHandlers as $paymentHandler) {
            $paymentHandlerClassName = $paymentHandler::class;

            $result = $this->profileService->updateProfileConfiguration(
                $paymentHandler,
                $this->requestEnricherChains[$paymentHandlerClassName],
                $scIdParam
            );

            if ($paymentHandler instanceof InvoicePaymentHandler) {
                $result = $this->convertInvoicingToInvoice($result, $scIdParam);
            }

            $results[$salesChannelId][] = $result;
        }

        $finalData = [];
        $responseKey = $scIdParam ?? 'null';

        foreach ($results as $scId => $updateResultsBySalesChannel) {
            $salesChannelData = [
                'updates' => [],
                'errors'  => [],
            ];
            foreach ($updateResultsBySalesChannel as $updateResult) {
                if (isset($updateResult['updates'])) $salesChannelData['updates'][] = $updateResult['updates'];
                if (isset($updateResult['errors'])) $salesChannelData['errors'][] = $updateResult['errors'];
            }
            $salesChannelData['updates'] = !empty($salesChannelData['updates']) ? \array_merge(...$salesChannelData['updates']) : [];
            $salesChannelData['errors'] = !empty($salesChannelData['errors']) ? \array_merge(...$salesChannelData['errors']) : [];

            $finalData['payoneRatepayProfilesUpdateResult'][$responseKey] = $salesChannelData;
        }

        return new JsonResponse($finalData);
    }

    // we need this "ing" change because of PayonePayment\Provider\Ratepay\PaymentMethod;
    private function syncInvoiceToInvoicing(?string $salesChannelId): void
    {
        $data = $this->systemConfigService->get('PayonePayment.settings.ratepayInvoiceProfiles', $salesChannelId);
        if (!empty($data)) {
            $this->systemConfigService->set('PayonePayment.settings.ratepayInvoicingProfiles', $data, $salesChannelId);
        }
    }

    private function convertInvoicingToInvoice(array $result, ?string $salesChannelId): array
    {
        $mappedUpdates = [];
        if (isset($result['updates'])) {
            foreach ($result['updates'] as $key => $value) {
                $newKey = str_replace('Invoicing', 'Invoice', $key);
                $mappedUpdates[$newKey] = $value;

                if ($key !== $newKey) {
                    $this->systemConfigService->set($newKey, $value, $salesChannelId);
                }
            }
        }

        $result['updates'] = $mappedUpdates;

        return $result;
    }
}
