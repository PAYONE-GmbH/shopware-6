<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\Service;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\RequestParameter\CalculateRequestDto;
use PayonePayment\Provider\Ratepay\RequestParameter\Enricher\Installment\CalculationRequestParameterEnricher;
use PayonePayment\Provider\Ratepay\RequestParameter\RequestEnricher;
use PayonePayment\Provider\Ratepay\Struct\Profile;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class InstallmentService
{
    public function __construct(
        private CartService $cartService,
        private PayoneClientInterface $client,
        private RequestEnricher $requestEnricher,
        private ProfileService $profileService,
        private InstallmentPaymentHandler $paymentHandler,
        private RequestParameterEnricherChain $requestEnricherChain,
    ) {
    }

    public function getInstallmentCalculatorData(
        SalesChannelContext $salesChannelContext,
        RequestDataBag|null $dataBag = null,
    ): RatepayInstallmentCalculatorData|null {
        $profile = $this->profileService->getProfileBySalesChannelContext(
            $salesChannelContext,
            InstallmentPaymentHandler::class,
        );

        if (null === $profile) {
            return null;
        }

        $profileConfiguration = $profile->getConfiguration();

        if (!isset($profileConfiguration['month-allowed']) || '' === $profileConfiguration['month-allowed']) {
            return null;
        }

        $allowedMonths = \explode(',', (string) $profileConfiguration['month-allowed']);

        $defaults = [
            'type'  => CalculationRequestParameterEnricher::INSTALLMENT_TYPE_TIME,
            'value' => $allowedMonths[0],
        ];

        if (null === $dataBag) {
            $dataBag = new RequestDataBag();
        }

        if (!$dataBag->has('ratepayInstallmentType') || !$dataBag->has('ratepayInstallmentValue')) {
            $dataBag->set('ratepayInstallmentType', $defaults['type']);
            $dataBag->set('ratepayInstallmentValue', $defaults['value']);
        }

        $calculationResponse = $this->getCalculation($dataBag, $profile, $salesChannelContext);

        $data = new RatepayInstallmentCalculatorData();

        $data->assign([
            'minimumRate'         => (float) $profileConfiguration['interestrate-min'],
            'maximumRate'         => (float) $profileConfiguration['interestrate-max'],
            'allowedMonths'       => $allowedMonths,
            'defaults'            => $defaults,
            'calculationParams'   => $dataBag->all(),
            'calculationResponse' => $calculationResponse,
        ]);

        return $data;
    }

    protected function getCalculation(
        RequestDataBag $dataBag,
        Profile $profile,
        SalesChannelContext $salesChannelContext,
    ): array {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $calculationRequest = $this->requestEnricher->enrich(
            new CalculateRequestDto(
                $salesChannelContext,
                $this->paymentHandler,
                false,
                $dataBag,
                $cart,
                $profile,
            ),

            $this->requestEnricherChain,
        );

        return $this->client->request($calculationRequest->all());
    }
}
