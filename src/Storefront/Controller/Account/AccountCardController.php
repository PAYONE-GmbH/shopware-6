<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Account;

use PayonePayment\StoreApi\Route\AbstractCardRoute;
use PayonePayment\Storefront\Page\Card\AccountCardPageLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountCardController extends StorefrontController
{
    public function __construct(private readonly AccountCardPageLoader $accountCardPageLoader, private readonly AbstractCardRoute $cardRoute)
    {
    }

    /**
     * @Route("/account/card/overview", name="frontend.account.payone.card.page", options={"seo": "false"}, methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function cardOverview(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->accountCardPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/payone/account/card.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/account/card/delete", name="frontend.account.payone.card.delete", options={"seo": "false"}, methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function deleteCard(Request $request, SalesChannelContext $context): Response
    {
        try {
            $this->cardRoute->delete($request->get('pseudoCardPan'), $context);
        } catch (\Throwable) {
            $this->addFlash('danger', $this->trans('PayonePayment.cardPage.error'));

            return $this->forwardToRoute('frontend.account.payone.card.page');
        }

        $this->addFlash('success', $this->trans('PayonePayment.cardPage.success'));

        return new RedirectResponse($this->generateUrl('frontend.account.payone.card.page'));
    }
}
