<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Components\GenericExpressCheckout\CustomerRegistrationUtil;
use PayonePayment\PaymentMethod\ExpressCheckoutPaymentMethodAwareInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class GenericExpressController extends StorefrontController
{
    final public const STATE_SUCCESS = 'success';

    final public const STATE_ERROR = 'error';

    final public const STATE_CANCEL = 'cancel';

    public function __construct(
        private readonly PaymentRequestEnricher $paymentRequestEnricher,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
        private readonly PayoneClientInterface $client,
        private readonly CartService $cartService,
        private readonly AbstractRegisterRoute $registerRoute,
        private readonly AccountService $accountService,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly SalesChannelContextSwitcher $salesChannelContextSwitcher,
        private readonly CartExtensionService $cartExtensionService,
        private readonly CustomerRegistrationUtil $customerRegistrationUtil,
    ) {
    }

    #[Route(
        path: '/payone/express-checkout/create-session/{paymentMethodId}',
        name: 'frontend.account.payone.express-checkout.generic.create-session',
        options: [ 'seo' => false ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'GET' ],
    )]
    public function createSessionAction(SalesChannelContext $context, string $paymentMethodId): Response
    {
        $response = $this->createSession($context, $paymentMethodId);

        if (!isset($response['orderId'])) {
            throw new \RuntimeException(
                'generic express checkout: No orderId has been given for payment method id ' . $paymentMethodId,
            );
        }

        return new JsonResponse([
            'orderId' => $response['orderId'],
        ]);
    }

    #[Route(
        path: '/payone/express-checkout/redirect/{paymentMethodId}',
        name: 'frontend.account.payone.express-checkout.generic.redirect',
        options: [ 'seo' => false ],
        methods: [ 'GET' ],
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

        if (!isset($response['redirectUrl'])) {
            throw new \RuntimeException(
                'generic express checkout: No redirect URL has been given for payment method id ' . $paymentMethodId,
            );
        }

        return new RedirectResponse($response['redirectUrl']);
    }

    #[Route(
        path: '/payone/express-checkout/return/{paymentMethodId}/{state}',
        name: 'frontend.account.payone.express-checkout.generic.return',
        options: [ 'seo' => false ],
        methods: [ 'GET' ],
    )]
    public function returnAction(SalesChannelContext $context, string $paymentMethodId, string $state): Response
    {
        $paymentMethod = $this->paymentMethodRegistry->getById($paymentMethodId);

        if (!$paymentMethod instanceof ExpressCheckoutPaymentMethodAwareInterface) {
            throw $this->createNotFoundException();
        }

        if (self::STATE_SUCCESS !== $state) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }

        $cart          = $this->cartService->getCart($context->getToken(), $context);
        $cartExtension = $this->cartExtensionService->getCartExtensionForExpressCheckout($cart, $paymentMethodId);

        if (null === $cartExtension) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $paymentHandler = $paymentMethod->getPaymentHandler();

        $getRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                new RequestDataBag(),
                $context,
                $cart,
                $paymentHandler,
            ),

            $paymentHandler->getExpressCheckoutSessionEnricherChains()->getRequestEnricherChain,
        );

        try {
            $response = $this->client->request($getRequest->all());
        } catch (PayoneRequestException) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if (empty($response['addpaydata'])) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $customerDataBag = $this->customerRegistrationUtil->getCustomerDataBagFromGetCheckoutSessionResponse(
            $response,
            $context,
        );

        $customerResponse = $this->registerRoute->register($customerDataBag, $context, false);
        $customerId       = $customerResponse->getCustomer()->getId();
        $newContextToken  = $this->accountService->login($response['addpaydata']['email'], $context, true);

        // info: we need to pass the payment-method-id for creating the context & switching the context
        $newContext = $this->salesChannelContextFactory->create(
            $newContextToken,
            $context->getSalesChannel()->getId(),
            [
                SalesChannelContextService::CUSTOMER_ID       => $customerId,
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
            ],
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
    private function createSession(SalesChannelContext $salesChannelContext, string $paymentMethodId): array
    {
        $paymentMethod = $this->paymentMethodRegistry->getById($paymentMethodId);

        if (!$paymentMethod instanceof ExpressCheckoutPaymentMethodAwareInterface) {
            throw $this->createNotFoundException();
        }

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $salesChannelDataBag = new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
        ]);

        $this->salesChannelContextSwitcher->update($salesChannelDataBag, $salesChannelContext);

        $paymentHandler = $paymentMethod->getPaymentHandler();

        $setRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                new RequestDataBag(),
                $salesChannelContext,
                $cart,
                $paymentHandler,
            ),

            $paymentHandler->getExpressCheckoutSessionEnricherChains()->createEnricherChain,
        );

        try {
            $response = $this->client->request($setRequest->all());
        } catch (PayoneRequestException) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        $this->cartExtensionService->addCartExtensionForExpressCheckout(
            $cart,
            $salesChannelContext,
            $paymentMethodId,
            $response['workorderid'],
        );

        return [
            'orderId'     => $response['addpaydata']['orderId'] ?? null,
            'redirectUrl' => $response['redirecturl'] ?? null,
        ];
    }
}
