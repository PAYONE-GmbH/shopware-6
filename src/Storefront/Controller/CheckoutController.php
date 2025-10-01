<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
readonly class CheckoutController
{
    public function __construct(
        private CartExtensionService $extensionService,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/payone/delete-checkout-data',
        name: 'frontend.payone.delete-checkout-data',
    )]
    public function deleteCheckoutData(
        Request $request,
        SalesChannelContext $salesChannelContext,
    ): Response {
        $this->extensionService->removeExtensionData($salesChannelContext);

        $session = $request->getSession();
        if (method_exists($session, 'getFlashBag')) {
            $session->getFlashBag()->add(
                StorefrontController::SUCCESS,
                $this->translator->trans('PayonePayment.checkoutConfirmPage.express-checkout-canceled'),
            );
        }

        return new RedirectResponse($this->router->generate('frontend.checkout.confirm.page'));
    }
}
