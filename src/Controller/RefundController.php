<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\RefundHandler\RefundHandler;
use PayonePayment\Components\RefundHandler\RefundHandlerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RefundController extends AbstractController
{
    /**
     * @Route("/api/v{version}/_action/payone/refund", name="api.action.payone.refund", methods={"POST"})
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     */
    public function refundAction(Request $request, Context $context): JsonResponse
    {
        /** @var RefundHandlerInterface $refundService */
        $refundService = $this->container->get(RefundHandler::class);

        $orderId = $request->get('order');

        if (empty($orderId)) {
            throw new BadRequestHttpException();
        }

        /** @var EntityRepositoryInterface $transactionRepository */
        $transactionRepository = $this->container->get('order_transaction.repository');

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('order_transaction.order_id', $orderId)
        );
        $criteria->addAssociation('oder_transaction.order');
        $criteria->addAssociation('oder_transaction.order.orderCustomer');

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $transactionRepository->search($criteria, $context)->first();

        if (null === $orderTransaction) {
            throw new BadRequestHttpException();
        }

        $refundService->refundTransaction($orderTransaction, $context);

        return new JsonResponse(['status' => true]);
    }
}
