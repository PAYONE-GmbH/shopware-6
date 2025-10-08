<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\Service;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\Provider\Ratepay\RequestParameter\ProfileRequestDto;
use PayonePayment\Provider\Ratepay\RequestParameter\RequestEnricher;
use PayonePayment\Provider\Ratepay\Struct\Profile;
use PayonePayment\Provider\Ratepay\Struct\ProfileSearch;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\AddressCompareService;
use PayonePayment\Service\OrderLoaderService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

readonly class ProfileService
{
    final public const PAYMENT_KEYS = [
        DebitPaymentHandler::class       => 'elv',
        InstallmentPaymentHandler::class => 'installment',
        InvoicePaymentHandler::class     => 'invoice',
    ];

    public function __construct(
        private PayoneClientInterface $client,
        private RequestEnricher $requestEnricher,
        private SystemConfigService $systemConfigService,
        private OrderLoaderService $orderLoaderService,
        private CartService $cartService,
        private ConfigReader $configReader,
        private AddressCompareService $addressCompareService,
    ) {
    }

    public function getProfile(ProfileSearch $profileSearch): Profile|null
    {
        $paymentKey = self::PAYMENT_KEYS[$profileSearch->getPaymentHandler()];

        $profileConfiguration = $this->systemConfigService->get(
            $this->configReader->getConfigKeyByPaymentHandler(
                $profileSearch->getPaymentHandler(),
                'ProfileConfigurations',
            ),

            $profileSearch->getSalesChannelId(),
        );

        if (!\is_array($profileConfiguration)) {
            return null;
        }

        foreach ($profileConfiguration as $shopId => $configuration) {
            if (
                'yes' !== $configuration['delivery-address-' . $paymentKey]
                && $profileSearch->isNeedsAllowDifferentAddress()
            ) {
                continue;
            }

            $allowedBillingCountries = \explode(',', (string) $configuration['country-code-billing']);

            if (!\in_array($profileSearch->getBillingCountryCode(), $allowedBillingCountries, true)) {
                continue;
            }

            $allowedDeliveryCountries = \explode(',', (string) $configuration['country-code-delivery']);

            if (!\in_array($profileSearch->getShippingCountryCode(), $allowedDeliveryCountries, true)) {
                continue;
            }

            $allowedCurrencies = \explode(',', (string) $configuration['currency']);

            if (!\in_array($profileSearch->getCurrency(), $allowedCurrencies, true)) {
                continue;
            }

            if ($profileSearch->getTotalAmount() > $configuration['tx-limit-' . $paymentKey . '-max']) {
                continue;
            }

            if ($profileSearch->getTotalAmount() < $configuration['tx-limit-' . $paymentKey . '-min']) {
                continue;
            }

            $profile = new Profile();

            $profile->setShopId((string) $shopId);
            $profile->setConfiguration($configuration);

            return $profile;
        }

        return null;
    }

    /**
     * @template T of bool
     *
     * @param T $throwException
     *
     * @return Profile|null
     */
    public function getProfileByOrder(
        OrderEntity $order,
        string $paymentHandler,
        bool $throwException = false,
    ): Profile|null {
        $billingAddress  = $this->orderLoaderService->getOrderBillingAddress($order);
        $shippingAddress = $this->orderLoaderService->getOrderShippingAddress($order);
        $billingCountry  = $billingAddress->getCountry();
        $shippingCountry = $shippingAddress->getCountry();
        $currency        = $order->getCurrency();

        if (null === $billingCountry || null === $shippingCountry || null === $currency) {
            if ($throwException) {
                throw new \RuntimeException('no ratepay profile found');
            }

            return null;
        }

        $billingCountryIso  = $billingCountry->getIso();
        $shippingCountryIso = $shippingCountry->getIso();

        if (null === $billingCountryIso || null === $shippingCountryIso) {
            if ($throwException) {
                throw new \RuntimeException('no ratepay profile found');
            }

            return null;
        }

        $profileSearch = new ProfileSearch();

        $profileSearch->setBillingCountryCode($billingCountryIso);
        $profileSearch->setShippingCountryCode($shippingCountryIso);
        $profileSearch->setPaymentHandler($paymentHandler);
        $profileSearch->setSalesChannelId($order->getSalesChannelId());
        $profileSearch->setCurrency($currency->getIsoCode());

        $profileSearch->setNeedsAllowDifferentAddress(
            !$this->addressCompareService->areOrderAddressesIdentical(
                $billingAddress,
                $shippingAddress,
            ),
        );

        $profileSearch->setTotalAmount($order->getPrice()->getTotalPrice());

        $profile = $this->getProfile($profileSearch);

        if (null === $profile && $throwException) {
            throw new \RuntimeException('no ratepay profile found');
        }

        return $profile;
    }

    public function getProfileBySalesChannelContext(
        SalesChannelContext $salesChannelContext,
        string $paymentHandler,
    ): Profile|null {
        $customer = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return null;
        }

        $billingAddress  = $customer->getActiveBillingAddress();
        $shippingAddress = $customer->getActiveShippingAddress();

        if (null === $billingAddress || null === $shippingAddress) {
            return null;
        }

        $billingCountry  = $billingAddress->getCountry();
        $shippingCountry = $shippingAddress->getCountry();

        if (null === $billingCountry || null === $shippingCountry) {
            return null;
        }

        $billingCountryIso  = $billingCountry->getIso();
        $shippingCountryIso = $shippingCountry->getIso();

        if (null === $billingCountryIso || null === $shippingCountryIso) {
            return null;
        }

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $profileSearch = new ProfileSearch();

        $profileSearch->setBillingCountryCode($billingCountryIso);
        $profileSearch->setShippingCountryCode($shippingCountryIso);
        $profileSearch->setPaymentHandler($paymentHandler);
        $profileSearch->setSalesChannelId($salesChannelContext->getSalesChannelId());
        $profileSearch->setCurrency($salesChannelContext->getCurrency()->getIsoCode());

        $profileSearch->setNeedsAllowDifferentAddress(
            !$this->addressCompareService->areCustomerAddressesIdentical(
                $billingAddress,
                $shippingAddress,
            ),
        );

        $profileSearch->setTotalAmount($cart->getPrice()->getTotalPrice());

        return $this->getProfile($profileSearch);
    }

    public function updateProfileConfiguration(
        PaymentHandlerInterface $paymentHandler,
        RequestParameterEnricherChain $enricherChain,
        string|null $salesChannelId = null,
    ): array {
        $paymentHandlerClassName = $paymentHandler::class;

        $profilesConfigKey = $this->configReader->getConfigKeyByPaymentHandler($paymentHandlerClassName, 'Profiles');

        $profileConfigurationsKey = $this->configReader->getConfigKeyByPaymentHandler(
            $paymentHandlerClassName,
            'ProfileConfigurations',
        );

        $configUpdates = [];
        $errors        = [];

        $profiles = $this->systemConfigService->get($profilesConfigKey, $salesChannelId);

        if (!\is_array($profiles)) {
            $profiles = [];
        }

        $validProfiles          = [];
        $configurationResponses = [];

        foreach ($profiles as $profile) {
            $shopId   = $profile['shopId'];
            $currency = $profile['currency'];

            if (empty($shopId) || empty($currency)) {
                $profile['error']             = 'Shop ID or Currency missing';
                $errors[$profilesConfigKey][] = $profile;

                continue;
            }

            $profileRequest = $this->requestEnricher->enrich(
                new ProfileRequestDto(
                    $paymentHandler,
                    false,
                    $salesChannelId ?? '',
                    $shopId,
                    $currency,
                ),

                $enricherChain,
            );

            try {
                $response = $this->client->request($profileRequest->all());
            } catch (PayoneRequestException $exception) {
                $profile['error']             = $exception->getResponse()['error']['ErrorMessage'];
                $errors[$profilesConfigKey][] = $profile;

                continue;
            }

            $configurationResponses[$shopId] = $response['addpaydata'];
            $validProfiles[$shopId]          = $profile;
        }

        $validProfiles = array_values($validProfiles);

        $this->systemConfigService->set(
            $profilesConfigKey,
            $validProfiles,
            $salesChannelId,
        );

        $this->systemConfigService->set(
            $profileConfigurationsKey,
            $configurationResponses,
            $salesChannelId,
        );

        $configUpdates[$profilesConfigKey]        = $validProfiles;
        $configUpdates[$profileConfigurationsKey] = $configurationResponses;

        return [
            'updates' => $configUpdates,
            'errors'  => $errors,
        ];
    }
}
