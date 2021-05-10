<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Account;

use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\Storefront\Page\Mandate\AccountMandatePageLoader;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class AccountMandateController extends StorefrontController
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
     * @RouteScope(scopes={"storefront"})
     * @Route("/account/mandate/overview", name="frontend.account.payone.mandate.page", options={"seo": "false"}, methods={"GET"})
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $page = $this->accountMandatePageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/payone/account/mandate.html.twig', ['page' => $page]);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/account/mandate/download", name="frontend.account.payone.mandate.download", options={"seo": "false"}, methods={"GET"})
     */
    public function downloadMandate(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        try {
            if (null !== $context->getCustomer()) {
                $file = $this->mandateService->downloadMandate(
                    $context->getCustomer(),
                    $request->get('mandate'),
                    $context
                );
            }
        } catch (Throwable $exception) {
            $this->addFlash('danger', $this->trans('PayonePayment.mandatePage.error'));

            return $this->forwardToRoute('frontend.account.payone.mandate.page');
        }

        $response = new Response($file ?? '');

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'mandate.pdf'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * refactor: Implementing core legacy code is imho not the best solution
     *
     * This method has been removed with SW 6.4. It is recommended to use LoginRequired annotation instead.
     * The annotation is not supported by SW 6.2.
     *
     * @throws CustomerNotLoggedInException
     */
    protected function denyAccessUnlessLoggedIn(bool $allowGuest = false): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request      = $requestStack->getCurrentRequest();

        if (!$request) {
            throw new CustomerNotLoggedInException();
        }

        /** @var null|SalesChannelContext $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (
            $context
            && $context->getCustomer()
            && (
                $allowGuest === true
                || $context->getCustomer()->getGuest() === false
            )
        ) {
            return;
        }

        throw new CustomerNotLoggedInException();
    }
}
