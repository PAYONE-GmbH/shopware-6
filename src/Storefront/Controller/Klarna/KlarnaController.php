<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Klarna;

use PayonePayment\Components\KlarnaSessionService\KlarnaSessionServiceInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class KlarnaController extends StorefrontController
{

    private KlarnaSessionServiceInterface $klarnaSessionService;
    private TranslatorInterface $translator;

    public function __construct(
        KlarnaSessionServiceInterface $klarnaSessionService,
        TranslatorInterface $translator
    )
    {
        $this->klarnaSessionService = $klarnaSessionService;
        $this->translator = $translator;
    }

    /**
     * @Route("/payone/klarna/create-session", name="frontend.payone.klarna.create-session", defaults={"methods"={"POST"}, "csrf_protected": false, "XmlHttpRequest": true})
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $orderId = $request->get('orderId');

        try {
            $sessionStruct = $this->klarnaSessionService->createKlarnaSession($salesChannelContext, $orderId);
            $data = $sessionStruct->toArray();
            $data['status'] = true;
        } catch (PayoneRequestException $e) {
            $data = [
                'status' => false,
                'errors' => $this->translator->trans('PayonePayment.errorMessages.canNotInitKlarna')
            ];
        }

        return new JsonResponse($data, $data['status'] ? 200 : 400);
    }
}
