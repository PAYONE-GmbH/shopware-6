<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\ApplePay;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\ApplePayTransactionStruct;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var CartService */
    protected $cartService;

    /** @var CurrencyPrecisionInterface */
    protected $currencyPrecision;

    public function __construct(CartService $cartService, CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->cartService       = $cartService;
        $this->currencyPrecision = $currencyPrecision;
    }

    /** @param ApplePayTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();
        $customer            = $salesChannelContext->getCustomer();
        $currency            = $salesChannelContext->getCurrency();
        $tokenData           = $arguments->getRequestData()->all();
        $cart                = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        if (null === $customer || null === $customer->getActiveBillingAddress() || null === $customer->getActiveBillingAddress()->getCountry()) {
            return [];
        }

        $billingAddress = $customer->getActiveBillingAddress();
        /** @var CountryEntity $country */
        $country = $billingAddress->getCountry();

        return [
            'wallettype'   => 'APL',
            'clearingtype' => self::CLEARING_TYPE_WALLET,
            'request'      => self::REQUEST_ACTION_AUTHORIZE,

            'lastname'  => $customer->getLastName(),
            'firstname' => $customer->getFirstName(),
            'country'   => $country->getIso(),

            'currency' => $currency->getIsoCode(),
            'cardtype' => $this->getCardType($arguments->getRequestData()),

            //TODO: remove
            'amount' => $this->currencyPrecision->getRoundedTotalAmount(0.01, $currency),
            //'amount' => $this->currencyPrecision->getRoundedTotalAmount($cart->getPrice()->getTotalPrice(), $currency),

            'reference' => substr($tokenData['paymentData']['header']['transactionId'], 0, 20) ?? bin2hex(random_bytes(8)),

            'add_paydata[paymentdata_token_version]'             => $tokenData['paymentData']['version'] ?? 'EC_v1',
            'add_paydata[paymentdata_token_data]'                => $tokenData['paymentData']['data'] ?? '',
            'add_paydata[paymentdata_token_signature]'           => $tokenData['paymentData']['signature'] ?? '',
            'add_paydata[paymentdata_token_ephemeral_publickey]' => $tokenData['paymentData']['header']['ephemeralPublicKey'] ?? '',
            'add_paydata[paymentdata_token_publickey_hash]'      => $tokenData['paymentData']['header']['publicKeyHash'] ?? '',
            'add_paydata[paymentdata_token_transaction_id]'      => $tokenData['paymentData']['header']['transactionId'] ?? '',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof ApplePayTransactionStruct)) {
            return false;
        }

        return $arguments->getAction() === self::REQUEST_ACTION_AUTHORIZE;
    }

    private function getCardType(ParameterBag $requestDataBag): string
    {
        $paymentMethod = $requestDataBag->get('paymentMethod', new RequestDataBag());

        return strtoupper(substr($paymentMethod->get('network', '?'), 0, 1));
    }
}
