<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @Route("/api/v{version}/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials", methods={"GET"})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $merchantId      = $request->query->get('merchantId');
        $accountId       = $request->query->get('accountId');
        $portalId        = $request->query->get('portalId');
        $portalKey       = $request->query->get('portalKey');
        $transactionMode = $request->query->getBoolean('transactionMode');

        $credentialsValid = true; // TODO: Verify credentials

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }
}
