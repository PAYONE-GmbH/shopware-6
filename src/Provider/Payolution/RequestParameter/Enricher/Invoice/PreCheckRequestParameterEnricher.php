<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Invoice;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum as GeneralRequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class PreCheckRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private OrderLoaderService $orderLoaderService,
        private ConfigReaderInterface $configReader,
        private RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $dataBag             = $arguments->requestData;
        $salesChannelContext = $arguments->salesChannelContext;
        $currency            = $this->orderLoaderService->getOrderCurrency(null, $salesChannelContext->getContext());
        $cart                = $arguments->cart;

        $amount = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
            $cart->getPrice()->getTotalPrice(),
            $currency,
        );

        $parameters = [
            'request'                   => GeneralRequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Invoicing',
            'clearingtype'              => PayoneClearingEnum::FINANCING->value,
            'financingtype'             => 'PYV',
            'amount'                    => $amount,
            'currency'                  => $currency->getIsoCode(),
            'workorderid'               => $dataBag->get(RequestConstantsEnum::WORK_ORDER_ID->value, ''),
        ];

        if (!empty($dataBag->get(RequestConstantsEnum::BIRTHDAY->value))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get(RequestConstantsEnum::BIRTHDAY->value));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($parameters, $salesChannelContext);
        }

        return $parameters;
    }

    protected function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA));
    }

    protected function provideCompanyParams(array &$parameters, SalesChannelContext $salesChannelContext): void
    {
        $customer = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return;
        }

        if ($billingAddress->getCompany() || $customer->getCompany()) {
            $parameters['add_paydata[b2b]'] = 'yes';

            $vatIds = $customer->getVatIds();

            if (\is_array($vatIds) && isset($vatIds[0])) {
                $parameters['add_paydata[company_uid]'] = $vatIds[0];
            }
        }
    }
}
