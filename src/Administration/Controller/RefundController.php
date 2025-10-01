<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Controller;

use PayonePayment\Components\TransactionHandler\Refund\RefundTransactionHandlerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'api' ] ])]
class RefundController extends AbstractController
{
    public function __construct(
        private readonly RefundTransactionHandlerInterface $refundHandler,
    ) {
    }

    #[Route(
        path: '/api/_action/payone/refund-payment',
        name: 'api.action.payone.refund_payment',
        methods: ['POST'],
    )]
    public function refundAction(Request $request, Context $context): JsonResponse
    {
        if (empty($request->get('orderTransactionId'))) {
            return new JsonResponse([
                'status'  => false,
                'message' => 'missing order transaction id',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->refundHandler->refund($request->request, $context);
    }
}
