<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RefundController extends AbstractController
{
    /**
     * @Route("/api/v{version}/_action/payone/refund", name="api.action.payone.refund", methods={"POST"})
     */
    public function refundAction(): JsonResponse
    {
        return new JsonResponse(['You successfully created your first API route']);
    }
}
