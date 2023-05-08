<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Ratepay;

use PayonePayment\Components\Ratepay\Installment\InstallmentServiceInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class RatepayController extends StorefrontController
{
    public function __construct(private readonly InstallmentServiceInterface $installmentService)
    {
    }

    #[Route(path: '/payone/ratepay/installment/calculation', name: 'frontend.payone.ratepay.installment.calculation', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function calculation(RequestDataBag $dataBag, SalesChannelContext $context): Response
    {
        try {
            $installmentPlan = $this->installmentService->getInstallmentCalculatorData($context, $dataBag);
        } catch (\Throwable) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        if ($installmentPlan === null) {
            throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        return $this->renderStorefront('@Storefront/storefront/payone/ratepay/ratepay-installment-plan.html.twig', [
            'calculationResponse' => $installmentPlan->getCalculationResponse(),
        ]);
    }
}
