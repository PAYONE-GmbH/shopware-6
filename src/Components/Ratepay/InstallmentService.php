<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

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

    public function __construct(
        CartService $cartService,
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory
    ) {
        $this->cartService             = $cartService;
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
    }

    public function getInstallmentCalculatorData(?RequestDataBag $dataBag = null): RatepayInstallmentCalculatorData
    {
        $defaults = [
            'type'  => CalculationRequestParameterBuilder::INSTALLMENT_TYPE_TIME,
            'value' => '', // ToDo: Get first allowed month from profile
        ];

        if ($dataBag === null) {
            $dataBag = new RequestDataBag();
        }

        if (!$dataBag->has('ratepayInstallmentType') || !$dataBag->has('ratepayInstallmentValue')) {
            $dataBag->set('ratepayInstallmentType', $defaults['type']);
            $dataBag->set('ratepayInstallmentValue', $defaults['value']);
        }

        $data = new RatepayInstallmentCalculatorData();
        $data->assign([
            'minimumRate'         => 0, // ToDo: Get from profile
            'maximumRate'         => 0, // ToDo: Get from profile
            'allowedMonths'       => [], // ToDo: Get from profile
            'debitPayType'        => '', // ToDo: Get from profile
            'defaults'            => $defaults,
            'calculationParams'   => $dataBag->all(),
            'calculationResponse' => [],    // ToDo: Add response here
        ]);

        return $data;
    }

    protected function getCalculation(RequestDataBag $dataBag, SalesChannelContext $context): array
    {
        // ToDo: Check if a calculation is stored in cart?

        $cart = $this->cartService->getCart($context->getToken(), $context);

        $calculationRequest = $this->requestParameterFactory->getRequestParameter(
            new RatepayCalculationStruct(
                $cart,
                $dataBag,
                $context,
                PayoneRatepayInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
            )
        );

        return $this->client->request($calculationRequest);
    }
}
