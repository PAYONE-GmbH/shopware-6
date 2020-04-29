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
        $transactionId = $request->get('orderTransactionId');

        if (empty($transactionId)) {
            return new JsonResponse(['status' => false, 'message' => 'missing order transaction id'], Response::HTTP_NOT_FOUND);
        }

        if((bool) $request->get('complete', false)) {
            return $this->captureHandler->fullCapture($transactionId, $context);
        }

        return $this->captureHandler->partialCapture($request->request, $context);
    }
}
