<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\ApplePay;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\ApplePayTransactionStruct;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\IdStruct;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        protected CartService $cartService,
        protected NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        protected EntityRepository $orderRepository
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param ApplePayTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        $currency = $salesChannelContext->getCurrency();
        $tokenData = $arguments->getRequestData()->all();
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $amount = $cart->getPrice()->getTotalPrice();

        if ($customer === null) {
            return [];
        }

        $billingAddress = $customer->getActiveBillingAddress();
        if ($billingAddress === null || $billingAddress->getCountry() === null) {
            return [];
        }

        /** @var CountryEntity $country */
        $country = $billingAddress->getCountry();

        $order = $this->getOrderById($arguments);

        if ($order !== null) {
            /** @var CurrencyEntity $currency */
            $currency = $order->getCurrency();
            /** @var OrderAddressCollection $addresses */
            $addresses = $order->getAddresses();
            /** @var OrderAddressEntity $billingAddress */
            $billingAddress = $addresses->get($order->getBillingAddressId());
            /** @var CountryEntity $country */
            $country = $billingAddress->getCountry();
            $amount = $order->getAmountTotal();
        }

        return [
            'wallettype' => 'APL',
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'request' => self::REQUEST_ACTION_AUTHORIZE,

            'lastname' => $billingAddress->getFirstName(),
            'firstname' => $billingAddress->getLastName(),
            'country' => $country->getIso(),

            'currency' => $currency->getIsoCode(),
            'cardtype' => $this->getCardType($arguments->getRequestData()),

            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($amount, $currency),

            'reference' => substr($this->getReferenceNumber($arguments, $cart, $order), 0, 20),

            'add_paydata[paymentdata_token_version]' => $tokenData['paymentData']['version'] ?? 'EC_v1',
            'add_paydata[paymentdata_token_data]' => $tokenData['paymentData']['data'] ?? '',
            'add_paydata[paymentdata_token_signature]' => $tokenData['paymentData']['signature'] ?? '',
            'add_paydata[paymentdata_token_ephemeral_publickey]' => $tokenData['paymentData']['header']['ephemeralPublicKey'] ?? '',
            'add_paydata[paymentdata_token_publickey_hash]' => $tokenData['paymentData']['header']['publicKeyHash'] ?? '',
            'add_paydata[paymentdata_token_transaction_id]' => $tokenData['paymentData']['header']['transactionId'] ?? '',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof ApplePayTransactionStruct)) {
            return false;
        }

        return $arguments->getAction() === self::REQUEST_ACTION_AUTHORIZE;
    }

    /**
     * @param ApplePayTransactionStruct $arguments
     */
    protected function getReferenceNumber(AbstractRequestParameterStruct $arguments, Cart $cart, ?OrderEntity $order): string
    {
        $referenceNumber = $this->getReferenceForExistingOrder($order);

        if ($referenceNumber !== null) {
            return $referenceNumber;
        }

        $salesChannelContext = $arguments->getSalesChannelContext();

        $referenceNumber = $this->numberRangeValueGenerator->getValue(
            OrderDefinition::ENTITY_NAME,
            $salesChannelContext->getContext(),
            $salesChannelContext->getSalesChannel()->getId()
        );

        $cart->addExtension(OrderConverter::ORIGINAL_ORDER_NUMBER, new IdStruct($referenceNumber));
        $this->cartService->recalculate($cart, $salesChannelContext);

        return $referenceNumber;
    }

    /**
     * @param ApplePayTransactionStruct $arguments
     */
    private function getOrderById(AbstractRequestParameterStruct $arguments): ?OrderEntity
    {
        if (empty($arguments->getOrderId())) {
            return null;
        }

        $context = $arguments->getSalesChannelContext()->getContext();

        $criteria = new Criteria([$arguments->getOrderId()]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('billingAddress');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('addresses.country');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        return $order;
    }

    private function getCardType(ParameterBag $requestDataBag): string
    {
        $paymentMethod = $requestDataBag->get('paymentMethod', new RequestDataBag());

        return strtoupper(substr((string) $paymentMethod->get('network', '?'), 0, 1));
    }

    private function getReferenceForExistingOrder(?OrderEntity $order): ?string
    {
        if ($order === null) {
            return null;
        }

        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return sprintf('%s_%d', $order->getOrderNumber(), 0);
        }

        return sprintf('%s_%d', $order->getOrderNumber(), $transactions->count());
    }
}
