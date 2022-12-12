<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionInvoicing;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit\AuthorizeRequestParameterBuilder as PayolutionDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AuthorizeRequestParameterBuilder extends PayolutionDebitAuthorizeRequestParameterBuilder
{
    protected ConfigReaderInterface $configReader;

    protected OrderFetcherInterface $orderFetcher;

    public function __construct(ConfigReaderInterface $configReader, OrderFetcherInterface $orderFetcher)
    {
        $this->configReader = $configReader;
        $this->orderFetcher = $orderFetcher;
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction = $arguments->getPaymentTransaction();

        $parameters = [
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => 'PYV',
            'request' => self::REQUEST_ACTION_AUTHORIZE,
        ];

        $this->applyBirthdayParameterWithoutCustomField($parameters, $dataBag);

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayonePayolutionInvoicingPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA));
    }

    protected function provideCompanyParams(string $orderId, array &$parameters, Context $context): void
    {
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if ($order === null) {
            return;
        }

        $orderAddresses = $order->getAddresses();

        if ($orderAddresses === null) {
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
            } elseif (method_exists($orderCustomer, 'getVatIds')) {
                $vatIds = $orderCustomer->getVatIds();

                if ($vatIds !== null && \count($vatIds) > 0) {
                    $parameters['add_paydata[company_uid]'] = $vatIds[0];
                }
            }
        }
    }
}
