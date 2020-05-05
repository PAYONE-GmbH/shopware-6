<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use Exception;
use PayonePayment\Components\PaymentHandler\Refund\RefundPaymentHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RefundController extends AbstractController
{
    /** @var RefundPaymentHandlerInterface */
    private $refundHandler;

    public function __construct(RefundPaymentHandlerInterface $captureHandler) {$this->refundHandler = $captureHandler;}

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone/refund-payment", name="api.action.payone.refund_payment", methods={"POST"})
     */
    public function refundAction(Request $request, Context $context): JsonResponse
    {
        if (empty($request->get('orderTransactionId'))) {
            return new JsonResponse(['status' => false, 'message' => 'missing order transaction id'], Response::HTTP_NOT_FOUND);
        }

        if((bool) $request->get('complete', false)) {
            return $this->refundHandler->fullRefund($request->request, $context);
        }

        return $this->refundHandler->partialRefund($request->request, $context);
    }
}
