<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Klarna;

use PayonePayment\Components\KlarnaSessionService\KlarnaSessionServiceInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
class KlarnaController extends StorefrontController
{
    /** @var KlarnaSessionServiceInterface */
    private $klarnaSessionService;

    public function __construct(KlarnaSessionServiceInterface $klarnaSessionService)
    {
        $this->klarnaSessionService = $klarnaSessionService;
    }

    /**
     * @Route("/payone/klarna/create-session", name="frontend.payone.klarna.create-session", methods={"POST"}, defaults={"csrf_protected": false, "XmlHttpRequest": true})
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $orderId = $request->get('orderId');

        try {
            $sessionStruct  = $this->klarnaSessionService->createKlarnaSession($salesChannelContext, $orderId);
            $data           = $sessionStruct->toArray();
            $data['status'] = true;
        } catch (PayoneRequestException $e) {
            $data = [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }

        return new JsonResponse($data, $data['status'] ? 200 : 400);
    }
}
