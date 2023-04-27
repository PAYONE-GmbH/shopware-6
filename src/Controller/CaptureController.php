<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\TransactionHandler\Capture\CaptureTransactionHandlerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CaptureController extends AbstractController
{
    public function __construct(private readonly CaptureTransactionHandlerInterface $captureHandler)
    {
    }

    /**
     * @Route("/api/_action/payone/capture-payment", name="api.action.payone.capture_payment", methods={"POST"}, defaults={"_routeScope"={"api"}})
     * @Route("/api/v{version}/_action/payone/capture-payment", name="api.action.payone.capture_payment.legacy", methods={"POST"}, defaults={"_routeScope"={"api"}})
     */
    public function captureAction(Request $request, Context $context): JsonResponse
    {
        if (empty($request->get('orderTransactionId'))) {
            return new JsonResponse(['status' => false, 'message' => 'missing order transaction id'], Response::HTTP_NOT_FOUND);
        }

        return $this->captureHandler->capture($request->request, $context);
    }
}
