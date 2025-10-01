<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Service;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Storefront\Struct\SecuredInstallmentOption;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class SecuredInstallmentService
{
    public function __construct(
        private PaymentRequestEnricher $paymentRequestEnricher,
        private RequestParameterEnricherChain $requestEnricherChain,
        private SecuredInstallmentPaymentHandler $paymentHandler,
        private CartService $cartService,
        private PayoneClientInterface $client,
    ) {
    }

    public function getInstallmentOptions(
        SalesChannelContext $salesChannelContext,
        RequestDataBag|null $dataBag = null,
    ): SecuredInstallmentOptionsData {
        if (null === $dataBag) {
            $dataBag = new RequestDataBag();
        }

        $response = $this->loadInstallmentOptions($dataBag, $salesChannelContext);
        $options  = [];

        if (isset($response['addpaydata']) && \is_array($response['addpaydata'])) {
            $optionIndices = [];

            foreach ($response['addpaydata'] as $key => $value) {
                if (\str_starts_with($key, 'installment_option_id_')) {
                    $parts           = \explode('_', $key);
                    $optionIndices[] = (int) \end($parts);
                }
            }

            \sort($optionIndices);

            foreach ($optionIndices as $index) {
                $option = $this->getOption($response, $index);

                if ($option) {
                    $options[] = $option;
                }
            }
        }

        $data = new SecuredInstallmentOptionsData();

        $data->assign([ 'options' => $options ]);

        return $data;
    }

    protected function loadInstallmentOptions(RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $installmentOptionsRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                $dataBag,
                $salesChannelContext,
                $cart,
                $this->paymentHandler,
            ),

            $this->requestEnricherChain,
        );

        return $this->client->request($installmentOptionsRequest->all());
    }

    protected function getOption(array $response, int $optionNumber): ?SecuredInstallmentOption
    {
        $optionIdKey = 'installment_option_id_' . $optionNumber;
        if (!isset($response['addpaydata'][$optionIdKey])) {
            return null;
        }

        $payData = $response['addpaydata'];
        $option  = new SecuredInstallmentOption();

        $option->assign([
            'installmentOptionId'       => $payData['installment_option_id_' . $optionNumber],
            'amountValue'               => $this->toFloat($payData['amount_value']),
            'amountCurrency'            => $payData['amount_currency'],
            'totalAmountValue'          => $this->toFloat($payData['total_amount_value_' . $optionNumber]),
            'totalAmountCurrency'       => $payData['total_amount_currency_' . $optionNumber],
            'monthlyAmountValue'        => $this->toFloat($payData['monthly_amount_value_' . $optionNumber]),
            'monthlyAmountCurrency'     => $payData['monthly_amount_currency_' . $optionNumber],
            'lastRateAmountValue'       => $this->toFloat($payData['last_rate_amount_value_' . $optionNumber]),
            'lastRateAmountCurrency'    => $payData['last_rate_amount_currency_' . $optionNumber],
            'firstRateDate'             => new \DateTime($payData['first_rate_date_' . $optionNumber]),
            'nominalInterestRate'       => $this->toFloat($payData['nominal_interest_rate_' . $optionNumber]),
            'effectiveInterestRate'     => $this->toFloat($payData['effective_interest_rate_' . $optionNumber]),
            'numberOfPayments'          => (int) $payData['number_of_payments_' . $optionNumber],
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
