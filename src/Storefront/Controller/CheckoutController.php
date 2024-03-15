<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CheckoutController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route(path: '/payone/delete-checkout-data', name: 'frontend.payone.delete-checkout-data')]
    public function deleteCheckoutData(
        Request $request,
        SalesChannelContext $salesChannelContext
    ): Response {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cart->removeExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        $this->cartService->recalculate($cart, $salesChannelContext);

        $session = $request->getSession();
        if (method_exists($session, 'getFlashBag')) {
            $session->getFlashBag()->add(
                StorefrontController::SUCCESS,
                $this->translator->trans('PayonePayment.checkoutConfirmPage.express-checkout-canceled')
            );
        }

        return new RedirectResponse($this->router->generate('frontend.checkout.confirm.page'));
    }
}
