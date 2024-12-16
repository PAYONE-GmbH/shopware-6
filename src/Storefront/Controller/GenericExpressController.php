<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Components\GenericExpressCheckout\CustomerRegistrationUtil;
use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\Components\GenericExpressCheckout\Struct\GetCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use RuntimeException;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class GenericExpressController extends StorefrontController
{
    final public const STATE_SUCCESS = 'success';
    final public const STATE_ERROR = 'error';
    final public const STATE_CANCEL = 'cancel';

    public function __construct(
        private readonly PayoneClientInterface $client,
        private readonly CartService $cartService,
        private readonly AbstractRegisterRoute $registerRoute,
        private readonly AccountService $accountService,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly SalesChannelContextSwitcher $salesChannelContextSwitcher,
        private readonly RequestParameterFactory $requestParameterFactory,
        private readonly CartExtensionService $cartExtensionService,
        private readonly CustomerRegistrationUtil $customerRegistrationUtil
    ) {
    }

    #[Route(
        path: '/payone/express-checkout/create-session/{paymentMethodId}',
        name: 'frontend.account.payone.express-checkout.generic.create-session',
        options: ['seo' => false],
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET']
    )]
    public function createSessionAction(SalesChannelContext $context, string $paymentMethodId): Response
    {
        $response = $this->createSession($context, $paymentMethodId);

        if (!isset($response['orderId'])) {
            throw new RuntimeException('generic express checkout: No orderId has been given for payment method id ' . $paymentMethodId);
        }

        return new JsonResponse([
            'orderId' => $response['orderId'],
        ]);
    }

    #[Route(
        path: '/payone/express-checkout/redirect/{paymentMethodId}',
        name: 'frontend.account.payone.express-checkout.generic.redirect',
        options: ['seo' => false],
        methods: ['GET']
    )]
    public function redirectAction(SalesChannelContext $context, string $paymentMethodId): Response
    {
        try {
            $response = $this->createSession($context, $paymentMethodId);
        } catch (ConstraintViolationException $constraintViolationException) {
            return $this->forwardToRoute('frontend.checkout.confirm.page', [
                'formViolations' => $constraintViolationException,
            ]);
        } catch (CartException) {
            return $this->forwardToRoute('frontend.checkout.confirm.page');
        }

        if (!\array_key_exists('redirecturl', $response)) {
            throw new RuntimeException('generic express checkout: No redirect URL has been given for payment method id ' . $paymentMethodId);
        }

        return new RedirectResponse($response['redirecturl']);
    }

    #[Route(
        path: '/payone/express-checkout/return/{paymentMethodId}/{state}',
        name: 'frontend.account.payone.express-checkout.generic.return',
        options: ['seo' => false],
        methods: ['GET']
    )]
    public function returnAction(SalesChannelContext $context, string $paymentMethodId, string $state): Response
    {
        if (!\array_key_exists($paymentMethodId, PaymentHandlerGroups::GENERIC_EXPRESS)) {
            throw $this->createNotFoundException();
        }

        if ($state !== self::STATE_SUCCESS) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        $cartExtension = $this->cartExtensionService->getCartExtensionForExpressCheckout($cart, $paymentMethodId);

        if ($cartExtension === null) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $getRequest = $this->requestParameterFactory->getRequestParameter(
            new GetCheckoutSessionStruct(
                $context,
                $cartExtension->getWorkorderId(),
                PaymentHandlerGroups::GENERIC_EXPRESS[$paymentMethodId]
            )
        );

        try {
            $response = $this->client->request($getRequest);
        } catch (PayoneRequestException) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if (empty($response['addpaydata'])) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $customerDataBag = $this->customerRegistrationUtil->getCustomerDataBagFromGetCheckoutSessionResponse($response, $context->getContext());
        $customerResponse = $this->registerRoute->register($customerDataBag, $context, false);
        $customerId = $customerResponse->getCustomer()->getId();
        $newContextToken = $this->accountService->login($response['addpaydata']['email'], $context, true);

        // info: we need to pass the payment-method-id for creating the context & switching the context
        $newContext = $this->salesChannelContextFactory->create(
            $newContextToken,
            $context->getSalesChannel()->getId(),
            [
                SalesChannelContextService::CUSTOMER_ID => $customerId,
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
            ]
        );

        $this->salesChannelContextSwitcher->update(new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
        ]), $context);

        $cart = $this->cartService->getCart($newContext->getToken(), $context);
        $this->cartExtensionService->addCartExtension($cart, $newContext, $response['workorderid']);

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    /**
     * @return array{orderId: string|null, redirectUrl: string|null}
     */
    private function createSession(SalesChannelContext $context, string $paymentMethodId): array
    {
        if (!\array_key_exists($paymentMethodId, PaymentHandlerGroups::GENERIC_EXPRESS)) {
            throw $this->createNotFoundException();
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        $salesChannelDataBag = new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
        ]);

        $this->salesChannelContextSwitcher->update($salesChannelDataBag, $context);

        $setRequest = $this->requestParameterFactory->getRequestParameter(
            new CreateExpressCheckoutSessionStruct(
                $context,
                PaymentHandlerGroups::GENERIC_EXPRESS[$paymentMethodId]
            )
        );

        try {
            $response = $this->client->request($setRequest);
        } catch (PayoneRequestException) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $this->cartExtensionService->addCartExtensionForExpressCheckout($cart, $context, $paymentMethodId, $response['workorderid']);

        return [
            'orderId' => $response['addpaydata']['orderId'] ?? null,
            'redirectUrl' => $response['redirecturl'] ?? null,
        ];
    }
}
