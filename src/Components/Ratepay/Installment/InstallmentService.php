<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\Installment;

use PayonePayment\Components\Ratepay\Profile\Profile;
use PayonePayment\Components\Ratepay\Profile\ProfileServiceInterface;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment\CalculationRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class InstallmentService implements InstallmentServiceInterface
{
    /** @var CartService */
    private $cartService;

    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var ProfileServiceInterface */
    private $profileService;

    public function __construct(
        CartService $cartService,
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory,
        ProfileServiceInterface $profileService
    ) {
        $this->cartService             = $cartService;
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->profileService          = $profileService;
    }

    public function getInstallmentCalculatorData(SalesChannelContext $salesChannelContext, ?RequestDataBag $dataBag = null): ?RatepayInstallmentCalculatorData
    {
        $profile = $this->profileService->getProfileBySalesChannelContext(
            $salesChannelContext,
            PayoneRatepayInstallmentPaymentHandler::class
        );

        if ($profile === null) {
            return null;
        }

        $profileConfiguration = $profile->getConfiguration();
        $allowedMonths        = explode(',', $profileConfiguration['month-allowed']);

        if (\count($allowedMonths) === 0) {
            return null;
        }

        $defaults = [
            'type'  => CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME,
            'value' => $allowedMonths[0],
        ];

        if ($dataBag === null) {
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

    protected function getCalculation(RequestDataBag $dataBag, Profile $profile, SalesChannelContext $context): array
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $calculationRequest = $this->requestParameterFactory->getRequestParameter(
            new RatepayCalculationStruct(
                $cart,
                $dataBag,
                $context,
                $profile,
                PayoneRatepayInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
            )
        );

        return $this->client->request($calculationRequest);
    }
}
