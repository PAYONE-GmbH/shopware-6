<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Components\CardService\CardServiceInterface;
use PayonePayment\Storefront\Page\Card\AccountCardPageLoader;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class AccountCardController extends StorefrontController
{
    /** @var AccountCardPageLoader */
    private $accountCardPageLoader;

    /** @var CardServiceInterface */
    private $cardService;

    public function __construct(AccountCardPageLoader $accountCardPageLoader, CardServiceInterface $cardService)
    {
        $this->accountCardPageLoader = $accountCardPageLoader;
        $this->cardService           = $cardService;
    }

    /**
     * @Route("/account/card/overview", name="frontend.account.payone.card.page", options={"seo": "false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function cardOverview(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $page = $this->accountCardPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/payone/account/card.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/account/card/delete", name="frontend.account.payone.card.delete", methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function deleteCard(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        try {
            $this->cardService->removeCard(
                $context->getCustomer(),
                $request->get('truncatedCardPan'),
                $context->getContext()
            );
        } catch (Throwable $exception) {
            $this->addFlash('danger', $this->trans('error.' . $exception->getErrorCode()));

            return $this->forwardToRoute('frontend.account.payone.card.page', ['success' => false]);
        }

        $this->addFlash('success', $this->trans('account.paymentSuccess'));

        return new RedirectResponse($this->generateUrl('frontend.account.payone.card.page'));
    }
}
