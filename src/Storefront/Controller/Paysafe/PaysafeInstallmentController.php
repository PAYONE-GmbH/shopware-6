<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Paysafe;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaysafeInstallmentController extends StorefrontController
{
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/installment-check", name="frontend.account.payone.paysafe.installment-check", options={"seo": "false"}, methods={"GET", "POST"}, defaults={"id": null, "XmlHttpRequest": true})
     */
    public function check(SalesChannelContext $context): Response
    {
        return new Response('');
    }
}
