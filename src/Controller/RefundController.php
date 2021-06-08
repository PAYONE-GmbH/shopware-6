<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\TransactionHandler\Refund\RefundTransactionHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RefundController extends AbstractController
{
    /** @var RefundTransactionHandlerInterface */
    private $refundHandler;

    public function __construct(RefundTransactionHandlerInterface $refundHandler)
    {
        $this->refundHandler = $refundHandler;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/payone/refund-payment", name="api.action.payone.refund_payment", methods={"POST"})
     * @Route("/api/v{version}/_action/payone/refund-payment", name="api.action.payone.refund_payment.legacy", methods={"POST"})
     */
    public function refundAction(Request $request, Context $context): JsonResponse
    {
        if (empty($request->get('orderTransactionId'))) {
            return new JsonResponse(['status' => false, 'message' => 'missing order transaction id'], Response::HTTP_NOT_FOUND);
        }

        return $this->refundHandler->refund($request->request, $context);
    }
}
