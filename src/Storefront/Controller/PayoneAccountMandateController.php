<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\Storefront\Page\Mandate\AccountMandatePageLoader;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class PayoneAccountMandateController extends StorefrontController
{
    /** @var AccountMandatePageLoader */
    private $accountMandatePageLoader;

    /** @var MandateServiceInterface */
    private $mandateService;

    public function __construct(AccountMandatePageLoader $accountMandatePageLoader, MandateServiceInterface $MandateService)
    {
        $this->accountMandatePageLoader = $accountMandatePageLoader;
        $this->mandateService           = $MandateService;
    }

    /**
     * @Route("/account/mandate/overview", name="frontend.account.payone.mandate.page", options={"seo": "false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $page = $this->accountMandatePageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/payone/account/mandate.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/account/mandate/download", name="frontend.account.payone.mandate.download", methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function downloadMandate(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        try {
            $file = $this->mandateService->downloadMandate(
                $context->getCustomer(),
                $request->get('mandate'),
                $context
            );
        } catch (Throwable $exception) {
            $this->addFlash('danger', $this->trans('PayonePayment.mandatePage.error'));

            return $this->forwardToRoute('frontend.account.payone.mandate.page');
        }

        $response = new Response($file);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'mandate.pdf'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
