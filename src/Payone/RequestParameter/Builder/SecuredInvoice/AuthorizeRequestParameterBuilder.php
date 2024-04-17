<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecuredInvoice;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        protected OrderFetcherInterface $orderFetcher,
        protected AbstractDeviceFingerprintService $deviceFingerprintService
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $context = $salesChannelContext->getContext();
        $paymentTransaction = $arguments->getPaymentTransaction();
        $order = $this->getOrder($paymentTransaction->getOrder()->getId(), $context);
        $currency = $this->getOrderCurrency($order, $context);

        $parameters = [
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PIV,
            'request' => self::REQUEST_ACTION_AUTHORIZE,
            'add_paydata[device_token]' => $this->deviceFingerprintService->getDeviceIdentToken($salesChannelContext),
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($order->getAmountTotal(), $currency),
            'currency' => $currency->getIsoCode(),
        ];

        if ($order->getLineItems() !== null) {
            $parameters = array_merge($parameters, $this->serviceAccessor->lineItemHydrator->mapOrderLines($currency, $order, $context));
        }

        $this->applyPhoneParameter($order, $parameters, $dataBag, $context);
        $this->applyBirthdayParameter($order, $parameters, $dataBag->get('payoneInvoiceBirthday') ?? '', $context);
        $this->applyB2bParameters($order, $parameters);

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneSecuredInvoicePaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        // Load order to make sure all associations are set
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if ($order === null) {
            throw new \RuntimeException('missing order');
        }

        return $order;
    }

    protected function applyB2bParameters(OrderEntity $order, array &$parameters): void
    {
        $billingAddress = $order->getAddresses()?->get($order->getBillingAddressId());

        if ($billingAddress === null) {
            return;
        }

        $company = $billingAddress->getCompany();
        if ($company === null) {
            return;
        }

        $parameters['businessrelation'] = 'b2b';
        $parameters['company'] = $company;

        if ($billingAddress->getVatId()) {
            $parameters['vatid'] = $billingAddress->getVatId();
        } else {
            $vatIds = $order->getOrderCustomer()?->getVatIds();

            if (\is_array($vatIds) && isset($vatIds[0])) {
                $parameters['vatid'] = $vatIds[0];
            }
        }
    }
}
