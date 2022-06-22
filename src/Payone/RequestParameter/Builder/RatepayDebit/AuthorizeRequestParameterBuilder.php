<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Components\Ratepay\ProfileSearch;
use PayonePayment\Components\Ratepay\ProfileServiceInterface;
use PayonePayment\Core\Utils\AddressCompare;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var OrderFetcherInterface */
    protected $orderFetcher;

    /** @var ProfileServiceInterface */
    protected $profileService;

    public function __construct(OrderFetcherInterface $orderFetcher, ProfileServiceInterface $profileService)
    {
        $this->orderFetcher = $orderFetcher;
        $this->profileService = $profileService;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $order = $this->getOrder(
            $paymentTransaction->getOrder()->getId(),
            $salesChannelContext->getContext()
        );
        $profile = $this->getProfileByOrder($order, PayoneRatepayDebitPaymentHandler::class);

        $parameters = [
            'request'                                    => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype'                               => self::CLEARING_TYPE_FINANCING,
            'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
            'iban'                                       => $dataBag->get('ratepayIban'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[shop_id]' => $profile['shopId'],
        ];

        $this->applyPhoneParameter($order, $parameters, $dataBag);
        $this->applyBirthdayParameter($parameters, $dataBag);

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneRatepayDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        // Load order to make sure all associations are set
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if (null === $order) {
            throw new RuntimeException('missing order');
        }

        return $order;
    }

    protected function getProfileByOrder(OrderEntity $order, string $paymentHandler): array
    {
        $billingAddress = $this->getOrderBillingAddress($order);
        $shippingAddress = $this->getOrderShippingAddress($order);

        $profileSearch = new ProfileSearch();
        $profileSearch->setBillingCountryCode($billingAddress->getCountry()->getIso());
        $profileSearch->setShippingCountryCode($shippingAddress->getCountry()->getIso());
        $profileSearch->setPaymentHandler($paymentHandler);
        $profileSearch->setSalesChannelId($order->getSalesChannelId());
        $profileSearch->setCurrency($order->getCurrency()->getIsoCode());
        $profileSearch->setNeedsAllowDifferentAddress(!AddressCompare::areOrderAddressesIdentical($billingAddress, $shippingAddress));
        $profileSearch->setTotalAmount($order->getPrice()->getTotalPrice());

        $profile = $this->profileService->getProfile($profileSearch);
        if ($profile === null) {
            throw new RuntimeException('missing ratepay profile');
        }

        return $profile;
    }

    protected function applyPhoneParameter(OrderEntity $order, array &$parameters, ParameterBag $dataBag): void
    {
        $billingAddress = $this->getOrderBillingAddress($order);
        $submittedPhoneNumber = $dataBag->get('ratepayPhone');

        if (!empty($submittedPhoneNumber) && $submittedPhoneNumber !== $billingAddress->getPhoneNumber()) {
            $billingAddress->setPhoneNumber($submittedPhoneNumber);
            // ToDo: Save billing address
        }

        if (!$billingAddress->getPhoneNumber()) {
            throw new RuntimeException('missing phone number');
        }

        $parameters['telephonenumber'] = $billingAddress->getPhoneNumber();
    }

    protected function applyBirthdayParameter(array &$parameters, ParameterBag $dataBag): void
    {
        if (!empty($dataBag->get('ratepayBirthday'))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get('ratepayBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }
    }

    protected function getOrderBillingAddress(OrderEntity $order): OrderAddressEntity
    {
        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            throw new RuntimeException('missing order addresses');
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());

        if (null === $billingAddress) {
            throw new RuntimeException('missing order billing address');
        }

        return $billingAddress;
    }

    protected function getOrderShippingAddress(OrderEntity $order): OrderAddressEntity
    {
        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            throw new RuntimeException('missing order addresses');
        }

        $deliveries = $order->getDeliveries();
        if ($deliveries && $deliveries->first()) {
            $shippingAddressId = $deliveries->first()->getShippingOrderAddressId();

            /** @var OrderAddressEntity $shippingAddress */
            $shippingAddress = $orderAddresses->get($shippingAddressId);

            if ($shippingAddress) {
                return $shippingAddress;
            }
        }

        return $this->getOrderBillingAddress($order);
    }
}
