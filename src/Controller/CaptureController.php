<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use Exception;
use PayonePayment\Components\CapturePaymentHandler\CapturePaymentHandlerInterface;
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone/capture-payment", name="api.action.payone.capture_payment", methods={"POST"})
     */
    public function captureAction(Request $request, Context $context): JsonResponse
    {
        $transaction = $request->get('transaction');

        if (empty($transaction)) {
            return new JsonResponse(['status' => false, 'message' => 'missing order transaction id'], Response::HTTP_NOT_FOUND);
        }

        $criteria = new Criteria([$transaction]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('paymentMethod');

        /** @var null|OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->transactionRepository->search($criteria, $context)->first();

        if (null === $orderTransaction) {
            return new JsonResponse(['status' => false, 'message' => 'no order transaction found'], Response::HTTP_NOT_FOUND);
        }

        if (null === $orderTransaction->getOrder()) {
            return new JsonResponse(['status' => false, 'message' => 'no order found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // TODO: paysafe braucht einen eigneen capure handler (anderer request)
            $this->captureHandler->captureTransaction($orderTransaction, $context);
        } catch (PayoneRequestException $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getResponse()['error']['ErrorMessage'],
                    'code'    => $exception->getResponse()['error']['ErrorCode'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Exception $exception) {
            return new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMessage(),
                    'code'    => 0,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => true]);
    }
}
