<?php

declare(strict_types=1);

namespace PayonePayment\Components\SecuredInstallment;

use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\SecuredInstallmentOptionsStruct;
use PayonePayment\Storefront\Struct\SecuredInstallmentOption;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class InstallmentService implements InstallmentServiceInterface
{
    private CartService $cartService;

    private PayoneClientInterface $client;

    private RequestParameterFactory $requestParameterFactory;

    public function __construct(
        CartService $cartService,
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory
    ) {
        $this->cartService = $cartService;
        $this->client = $client;
        $this->requestParameterFactory = $requestParameterFactory;
    }

    public function getInstallmentOptions(SalesChannelContext $salesChannelContext, ?RequestDataBag $dataBag = null): SecuredInstallmentOptionsData
    {
        if ($dataBag === null) {
            $dataBag = new RequestDataBag();
        }

        $response = $this->loadInstallmentOptions($dataBag, $salesChannelContext);

        $data = new SecuredInstallmentOptionsData();
        $data->assign([
            'options' => [
                $this->getOption($response, 0),
                $this->getOption($response, 1),
                $this->getOption($response, 2),
            ],
        ]);

        return $data;
    }

    protected function loadInstallmentOptions(RequestDataBag $dataBag, SalesChannelContext $context): array
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $installmentOptionsRequest = $this->requestParameterFactory->getRequestParameter(
            new SecuredInstallmentOptionsStruct(
                $cart,
                $dataBag,
                $context,
                PayoneSecuredInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_SECURED_INSTALLMENT_OPTIONS
            )
        );

        return $this->client->request($installmentOptionsRequest);
    }

    protected function getOption(array $response, int $optionNumber): SecuredInstallmentOption
    {
        $option = new SecuredInstallmentOption();

        $option->assign([
            'installmentOptionId' => $response['addpaydata']['installment_option_id_' . $optionNumber],
            'amountValue' => $this->toFloat($response['addpaydata']['amount_value']),
            'amountCurrency' => $response['addpaydata']['amount_currency'],
            'totalAmountValue' => $this->toFloat($response['addpaydata']['total_amount_value_' . $optionNumber]),
            'totalAmountCurrency' => $response['addpaydata']['total_amount_currency_' . $optionNumber],
            'monthlyAmountValue' => $this->toFloat($response['addpaydata']['monthly_amount_value_' . $optionNumber]),
            'monthlyAmountCurrency' => $response['addpaydata']['monthly_amount_currency_' . $optionNumber],
            'lastRateAmountValue' => $this->toFloat($response['addpaydata']['last_rate_amount_value_' . $optionNumber]),
            'lastRateAmountCurrency' => $response['addpaydata']['last_rate_amount_currency_' . $optionNumber],
            'firstRateDate' => $response['addpaydata']['first_rate_date_' . $optionNumber],
            'nominalInterestRate' => $this->toFloat($response['addpaydata']['nominal_interest_rate_' . $optionNumber]),
            'effectiveInterestRate' => $this->toFloat($response['addpaydata']['effective_interest_rate_' . $optionNumber]),
            'numberOfPayments' => (int) $response['addpaydata']['number_of_payments_' . $optionNumber],
            'linkCreditInformationHref' => $response['addpaydata']['link_credit_information_href_' . $optionNumber],
            'linkCreditInformationType' => $response['addpaydata']['link_credit_information_type_' . $optionNumber],
        ]);

        return $option;
    }

    protected function toFloat(string $value): float
    {
        return ((int) $value) / 100;
    }
}
