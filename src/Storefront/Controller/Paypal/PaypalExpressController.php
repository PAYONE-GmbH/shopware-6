<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Paypal;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalGetExpressCheckoutDetailsRequestFactory;
use PayonePayment\Payone\Request\Paypal\PaypalSetExpressCheckoutRequestFactory;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class PaypalExpressController extends StorefrontController
{
    /** @var PaypalSetExpressCheckoutRequestFactory */
    private $checkoutRequestFactory;

    /** @var PaypalGetExpressCheckoutDetailsRequestFactory */
    private $checkoutDetailsRequestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var CartService */
    private $cartService;

    /** @var AccountRegistrationService */
    private $accountRegistrationService;

    /** @var AccountService */
    private $accountService;

    /** @var SalesChannelContextFactory */
    private $salesChannelContextFactory;

    /** @var EntityRepositoryInterface */
    private $salutationRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    /** @var SalesChannelContextSwitcher */
    private $salesChannelContextSwitcher;

    /** @var CartHasherInterface */
    private $cartHasher;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        PaypalSetExpressCheckoutRequestFactory $checkoutRequestFactory,
        PaypalGetExpressCheckoutDetailsRequestFactory $checkoutDetailsRequestFactory,
        PayoneClientInterface $client,
        CartService $cartService,
        AccountRegistrationService $accountRegistrationService,
        AccountService $accountService,
        SalesChannelContextFactory $salesChannelContextFactory,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $countryRepository,
        SalesChannelContextSwitcher $salesChannelContextSwitcher,
        CartHasherInterface $cartHasher,
        RouterInterface $router
    ) {
        $this->checkoutRequestFactory        = $checkoutRequestFactory;
        $this->checkoutDetailsRequestFactory = $checkoutDetailsRequestFactory;
        $this->client                        = $client;
        $this->cartService                   = $cartService;
        $this->accountRegistrationService    = $accountRegistrationService;
        $this->accountService                = $accountService;
        $this->salesChannelContextFactory    = $salesChannelContextFactory;
        $this->salutationRepository          = $salutationRepository;
        $this->countryRepository             = $countryRepository;
        $this->salesChannelContextSwitcher   = $salesChannelContextSwitcher;
        $this->cartHasher                    = $cartHasher;
        $this->router                        = $router;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/paypal/express-checkout", name="frontend.account.payone.paypal.express-checkout", options={"seo": "false"}, methods={"GET"})
     */
    public function express(SalesChannelContext $context): Response
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        try {
            $salesChannelDataBag = new DataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => PayonePaypalExpress::UUID,
            ]);

            $this->salesChannelContextSwitcher->update($salesChannelDataBag, $context);
        } catch (Throwable $exception) {
            return $this->forwardToRoute('frontend.checkout.confirm.page', [
                'formViolations' => $exception,
            ]);
        }

        $setRequest = $this->checkoutRequestFactory->getRequestParameters(
            $cart,
            $context,
            $this->generateReturnUrl()
        );

        try {
            $response = $this->client->request($setRequest);
        } catch (PayoneRequestException $exception) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $this->addCartExtenson($cart, $context, $response['workorderid']);

        return new RedirectResponse($response['redirecturl']);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/paypal/redirect-handler", name="frontend.account.payone.paypal.express-checkout-handler", options={"seo": "false"}, methods={"GET"})
     */
    public function redirectHandler(SalesChannelContext $context, Request $request): Response
    {
        try {
            $this->handleStateResponse($request->get('state'));
        } catch (Throwable $exception) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        /** @var null|CheckoutCartPaymentData $cartExtension */
        $cartExtension = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        if (null === $cartExtension) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $workOrderId = $cartExtension->getWorkorderId();
        $getRequest  = $this->checkoutDetailsRequestFactory->getRequestParameters($cart, $context, $workOrderId);

        try {
            $response = $this->client->request($getRequest);
        } catch (PayoneRequestException $exception) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if (empty($response['addpaydata'])) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $customerDataBag = $this->getCustomerDataBagFromResponse($response, $context->getContext());
        $customerId      = $this->accountRegistrationService->register($customerDataBag, true, $context);
        $newContextToken = $this->accountService->login($response['addpaydata']['email'], $context, true);

        $newContext = $this->salesChannelContextFactory->create(
            $newContextToken,
            $context->getSalesChannel()->getId(),
            [
                SalesChannelContextService::CUSTOMER_ID => $customerId,
            ]
        );

        $salesChannelDataBag = new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => PayonePaypalExpress::UUID,
        ]);

        $this->salesChannelContextSwitcher->update($salesChannelDataBag, $context);

        $cart = $this->cartService->getCart($newContext->getToken(), $context);
        $this->addCartExtenson($cart, $newContext, $response['workorderid']);

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    protected function addCartExtenson(
        Cart $cart,
        SalesChannelContext $context,
        string $workOrderId
    ): void {
        $cartData = new CheckoutCartPaymentData();

        $cartData->assign(array_filter([
            'workOrderId' => $workOrderId,
            'cartHash'    => $this->cartHasher->generate($cart, $context),
        ]));

        $cart->addExtension(CheckoutCartPaymentData::EXTENSION_NAME, $cartData);

        $this->cartService->recalculate($cart, $context);
    }

    private function generateReturnUrl(): string
    {
        return $this->router->generate('frontend.account.payone.paypal.express-checkout-handler', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getCustomerDataBagFromResponse(array $response, Context $context): DataBag
    {
        $salutationId = $this->getSalutationId($context);
        $countryId    = $this->getCountryIdByCode($response['addpaydata']['shipping_country'], $context);

        return new DataBag([
            'salutationId'   => $salutationId,
            'email'          => $response['addpaydata']['email'],
            'firstName'      => $response['addpaydata']['shipping_firstname'],
            'lastName'       => $response['addpaydata']['shipping_lastname'],
            'billingAddress' => array_filter([
                'firstName'              => $response['addpaydata']['shipping_firstname'],
                'lastName'               => $response['addpaydata']['shipping_lastname'],
                'salutationId'           => $salutationId,
                'street'                 => $response['addpaydata']['shipping_street'],
                'zipcode'                => $response['addpaydata']['shipping_zip'],
                'countryId'              => $countryId,
                'phone'                  => $response['addpaydata']['telephonenumber'],
                'city'                   => $response['addpaydata']['shipping_city'],
                'additionalAddressLine1' => isset($response['addpaydata']['shipping_addressaddition'])
                    ? $response['addpaydata']['shipping_addressaddition']
                    : null,
            ]),
        ]);
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salutationKey', 'not_specified')
        );

        /** @var null|SalutationEntity $salutation */
        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (null === $salutation) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        return $salutation->getId();
    }

    private function getCountryIdByCode(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('iso', $code)
        );

        /** @var null|CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (!$country instanceof CountryEntity) {
            return null;
        }

        return $country->getId();
    }

    private function handleStateResponse(string $state): void
    {
        if (empty($state)) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if ($state === 'cancel') {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if ($state === 'error') {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }
    }
}
