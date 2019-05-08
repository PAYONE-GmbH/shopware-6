<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\CapturePaymentHandler\CapturePaymentHandlerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class CaptureController extends AbstractController
{
    /** @var CapturePaymentHandlerInterface */
    private $captureHandler;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    public function __construct(
        CapturePaymentHandlerInterface $captureHandler,
        EntityRepositoryInterface $transactionRepository
    ) {
        $this->captureHandler        = $captureHandler;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @Route("/api/v{version}/_action/payone/capture-payment", name="api.action.payone.capture_payment", methods={"POST"})
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     */
    public function captureAction(Request $request, Context $context): JsonResponse
    {
        $orderId = $request->get('order');

        if (empty($orderId)) {
            return new JsonResponse(['status' => false, 'message' => 'missing order id']);
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('orderId', $orderId)
        );

        $criteria->addAssociation('order');

        /** @var null|OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $orderTransaction) {
            return new JsonResponse(['status' => false, 'message' => 'no order transaction found']);
        }

        try {
            $this->captureHandler->captureTransaction($orderTransaction, $context);
        } catch (Throwable $exception) {
            return new JsonResponse(['status' => false, 'message' => $exception->getMessage()]);
        }

        return new JsonResponse(['status' => true]);
    }
}
