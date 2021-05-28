<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalAuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var CartHasherInterface */
    private $cartHasher;

    public function __construct(CartHasherInterface $cartHasher)
    {
        $this->cartHasher = $cartHasher;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $requestData         = $arguments->getRequestData();
        $paymentMethod       = $arguments->getPaymentMethod();
        $currency            = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());
        $shippingAddress     = $salesChannelContext->getCustomer() !== null ? $salesChannelContext->getCustomer()->getActiveShippingAddress() : null;

        //TODO: may move amount and co to different handler
        $parameters = [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            //TODO: afterwards default parameter request
            'amount'      => $this->getConvertedAmount($paymentTransaction->getOrder()->getAmountTotal(), $currency->getDecimalPrecision()),
            'currency'    => $currency->getIsoCode(),
            'reference'   => $this->getReferenceNumber($paymentTransaction, true),
            'successurl'  => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=success'),
            'errorurl'    => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=error'),
            'backurl'     => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=cancel'),
            'workorderid' => $this->getWorkOrderId($paymentTransaction, $requestData, $salesChannelContext),
        ];

        if ($shippingAddress !== null) {
            $parameters = $this->applyShippingParameters($parameters, $shippingAddress);
        }

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES_BY_METHOD[$paymentMethod],
            (string) $paymentTransaction->getOrder()->getOrderNumber()
        );

        return $parameters;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePaypal::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    private function applyShippingParameters(array $parameters, CustomerAddressEntity $shippingAddress): array
    {
        //TODO: may separate request builder
        $shippingParameters = array_filter([
            'shipping_firstname' => $shippingAddress->getFirstName(),
            'shipping_lastname'  => $shippingAddress->getLastName(),
            'shipping_company'   => $shippingAddress->getCompany(),
            'shipping_street'    => $shippingAddress->getStreet(),
            'shipping_zip'       => $shippingAddress->getZipcode(),
            'shipping_city'      => $shippingAddress->getCity(),
            'shipping_country'   => $shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null,
        ]);

        return array_merge($parameters, $shippingParameters);
    }

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): ?string {
        $workOrderId = $dataBag->get('workorder');

        if (null === $workOrderId) {
            return null;
        }

        $cartHash = $dataBag->get('carthash');

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $workOrderId;
    }
}
