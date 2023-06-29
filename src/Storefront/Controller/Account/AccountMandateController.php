<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Account;

use PayonePayment\StoreApi\Route\AbstractMandateRoute;
use PayonePayment\Storefront\Page\Mandate\AccountMandatePageLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountMandateController extends StorefrontController
{
    private AccountMandatePageLoader $accountMandatePageLoader;

    private AbstractMandateRoute $mandateRoute;

    public function __construct(AccountMandatePageLoader $accountMandatePageLoader, AbstractMandateRoute $mandateRoute)
    {
        $this->accountMandatePageLoader = $accountMandatePageLoader;
        $this->mandateRoute = $mandateRoute;
    }

    /**
     * @Route("/account/mandate/overview", name="frontend.account.payone.mandate.page", options={"seo": "false"}, methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->accountMandatePageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/payone/account/mandate.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/account/mandate/download", name="frontend.account.payone.mandate.download", options={"seo": "false"}, methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function downloadMandate(Request $request, SalesChannelContext $context): Response
    {
        try {
            $response = $this->mandateRoute->getFile($request->get('mandate'), $context);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $this->trans('PayonePayment.mandatePage.error'));

            return $this->forwardToRoute('frontend.account.payone.mandate.page');
        }

        return $response;
    }
}
