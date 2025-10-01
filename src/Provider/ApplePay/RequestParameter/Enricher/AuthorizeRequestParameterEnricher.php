<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Provider\ApplePay\StoreApi\Route\ApplePayRoute;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\IdStruct;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        protected RequestBuilderServiceAccessor $serviceAccessor,
        protected CartService $cartService,
        protected NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $salesChannelContext = $arguments->salesChannelContext;
        $customer            = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return [];
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return [];
        }

        $country = $billingAddress->getCountry();

        if (null === $country) {
            return [];
        }

        $currency  = $salesChannelContext->getCurrency();
        $tokenData = $arguments->requestData->all();
        $cart      = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $amount    = $cart->getPrice()->getTotalPrice();
        $order     = $arguments->paymentTransaction->order;

        if (ApplePayRoute::EMPTY_ORDER_ID !== $order->getId()) {
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

        $amount          = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount($amount, $currency);
        $referenceNumber = \substr($this->getReferenceNumber($cart, $order, $salesChannelContext), 0, 20);

        return [
            'request'                                            => $requestActionEnum->value,
            'clearingtype'                                       => PayoneClearingEnum::WALLET->value,
            'wallettype'                                         => 'APL',
            'lastname'                                           => $billingAddress->getFirstName(),
            'firstname'                                          => $billingAddress->getLastName(),
            'country'                                            => $country->getIso(),
            'currency'                                           => $currency->getIsoCode(),
            'cardtype'                                           => $this->getCardType($arguments->requestData),
            'amount'                                             => $amount,
            'reference'                                          => $referenceNumber,
            'add_paydata[paymentdata_token_version]'             => $tokenData['paymentData']['version'] ?? 'EC_v1',
            'add_paydata[paymentdata_token_data]'                => $tokenData['paymentData']['data'] ?? '',
            'add_paydata[paymentdata_token_signature]'           => $tokenData['paymentData']['signature'] ?? '',
            'add_paydata[paymentdata_token_ephemeral_publickey]' => $tokenData['paymentData']['header']['ephemeralPublicKey'] ?? '',
            'add_paydata[paymentdata_token_publickey_hash]'      => $tokenData['paymentData']['header']['publicKeyHash'] ?? '',
            'add_paydata[paymentdata_token_transaction_id]'      => $tokenData['paymentData']['header']['transactionId'] ?? '',
        ];

    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }

    protected function getReferenceNumber(
        Cart $cart,
        OrderEntity|null $order,
        SalesChannelContext $salesChannelContext,
    ): string {
        $referenceNumber = $this->getReferenceForExistingOrder($order);

        if (null !== $referenceNumber) {
            return $referenceNumber;
        }

        $referenceNumber = $this->numberRangeValueGenerator->getValue(
            OrderDefinition::ENTITY_NAME,
            $salesChannelContext->getContext(),
            $salesChannelContext->getSalesChannel()->getId(),
        );

        $cart->addExtension(OrderConverter::ORIGINAL_ORDER_NUMBER, new IdStruct($referenceNumber));
        $this->cartService->recalculate($cart, $salesChannelContext);

        return $referenceNumber;
    }

    protected function getCardType(ParameterBag $requestDataBag): string
    {
        $paymentMethod = $requestDataBag->get('paymentMethod', new RequestDataBag());

        return \strtoupper(\substr((string) $paymentMethod->get('network', '?'), 0, 1));
    }

    protected function getReferenceForExistingOrder(OrderEntity|null $order): string|null
    {
        if (null === $order) {
            return null;
        }

        $transactions = $order->getTransactions();

        if (null === $transactions) {
            return \sprintf('%s_%d', $order->getOrderNumber(), 0);
        }

        return \sprintf('%s_%d', $order->getOrderNumber(), $transactions->count());
    }
}
