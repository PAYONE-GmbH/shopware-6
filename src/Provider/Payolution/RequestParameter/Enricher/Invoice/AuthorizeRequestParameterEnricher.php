<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher\Invoice;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\Enricher\ApplyBirthdayParameterTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ApplyBirthdayParameterTrait;

    public function __construct(
        protected ConfigReaderInterface $configReader,
        protected OrderLoaderService $orderLoaderService,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $salesChannelContext = $arguments->salesChannelContext;

        $parameters = [
            'request'       => $requestActionEnum->value,
            'clearingtype'  => PayoneClearingEnum::FINANCING->value,
            'financingtype' => 'PYV',
            'iban'          => $arguments->requestData->get('payolutionIban'),
            'bic'           => $arguments->requestData->get('payolutionBic'),
        ];

        $context = $arguments->salesChannelContext->getContext();
        $order   = $this->orderLoaderService->getOrderById(
            $arguments->paymentTransaction->order->getId(),
            $context,
            true,
        );

        /** @noinspection NullPointerExceptionInspection */
        $this->applyBirthdayParameter($order, $parameters, $arguments->requestData, $context);

        if ($this->transferCompanyData($salesChannelContext)) {
            /** @noinspection NullPointerExceptionInspection */
            $this->provideCompanyParams($order, $parameters);
        }

        return $parameters;
    }

    private function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA));
    }

    private function provideCompanyParams(OrderEntity $order, array &$parameters): void
    {
        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            return;
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());

        /** @var OrderCustomerEntity $orderCustomer */
        $orderCustomer = $order->getOrderCustomer();

        if ($billingAddress->getCompany() || $orderCustomer->getCompany()) {
            $parameters['add_paydata[b2b]'] = 'yes';

            if ($billingAddress->getVatId()) {
                $parameters['add_paydata[company_uid]'] = $billingAddress->getVatId();

                return;
            }

            $vatIds = $orderCustomer->getVatIds();

            if (\is_array($vatIds) && isset($vatIds[0])) {
                $parameters['add_paydata[company_uid]'] = $vatIds[0];
            }
        }
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }
}
