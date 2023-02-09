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

        $options = [];
        if (isset($response['addpaydata']) && \is_array($response['addpaydata'])) {
            $optionIndices = [];
            foreach ($response['addpaydata'] as $key => $value) {
                if (strpos($key, 'installment_option_id_') === 0) {
                    $parts = explode('_', $key);
                    $optionIndices[] = (int) end($parts);
                }
            }

            sort($optionIndices);

            foreach ($optionIndices as $index) {
                $option = $this->getOption($response, $index);
                if ($option) {
                    $options[] = $option;
                }
            }
        }

        $data = new SecuredInstallmentOptionsData();
        $data->assign([
            'options' => $options,
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

    protected function getOption(array $response, int $optionNumber): ?SecuredInstallmentOption
    {
        $optionIdKey = 'installment_option_id_' . $optionNumber;
        if (!isset($response['addpaydata'][$optionIdKey])) {
            return null;
        }

        $payData = $response['addpaydata'];
        $option = new SecuredInstallmentOption();

        $option->assign([
            'installmentOptionId' => $payData['installment_option_id_' . $optionNumber],
            'amountValue' => $this->toFloat($payData['amount_value']),
            'amountCurrency' => $payData['amount_currency'],
            'totalAmountValue' => $this->toFloat($payData['total_amount_value_' . $optionNumber]),
            'totalAmountCurrency' => $payData['total_amount_currency_' . $optionNumber],
            'monthlyAmountValue' => $this->toFloat($payData['monthly_amount_value_' . $optionNumber]),
            'monthlyAmountCurrency' => $payData['monthly_amount_currency_' . $optionNumber],
            'lastRateAmountValue' => $this->toFloat($payData['last_rate_amount_value_' . $optionNumber]),
            'lastRateAmountCurrency' => $payData['last_rate_amount_currency_' . $optionNumber],
            'firstRateDate' => new \DateTime($payData['first_rate_date_' . $optionNumber]),
            'nominalInterestRate' => $this->toFloat($payData['nominal_interest_rate_' . $optionNumber]),
            'effectiveInterestRate' => $this->toFloat($payData['effective_interest_rate_' . $optionNumber]),
            'numberOfPayments' => (int) $payData['number_of_payments_' . $optionNumber],
            'linkCreditInformationHref' => $payData['link_credit_information_href_' . $optionNumber],
            'linkCreditInformationType' => $payData['link_credit_information_type_' . $optionNumber],
        ]);

        return $option;
    }

    protected function toFloat(string $value): float
    {
        return ((int) $value) / 100;
    }
}
