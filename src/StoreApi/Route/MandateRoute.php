<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\StoreApi\Response\MandateResponse;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class MandateRoute extends AbstractMandateRoute
{
    public function __construct(private readonly MandateServiceInterface $mandateService)
    {
    }

    public function getDecorated(): AbstractMandateRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/payone/account/mandate', name: 'store-api.payone.account.mandate', defaults: ['_contextTokenRequired' => true, '_entity' => 'payone_payment_mandate'], methods: ['GET'])]
    public function load(SalesChannelContext $context): MandateResponse
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $result = $this->mandateService->getMandates($customer, $context);

        return new MandateResponse($result);
    }

    #[Route(path: '/store-api/payone/account/mandate/{mandateId}', name: 'store-api.payone.account.mandate.file', defaults: ['_contextTokenRequired' => true, '_entity' => 'payone_payment_mandate'], methods: ['GET'])]
    public function getFile(string $mandateId, SalesChannelContext $context): Response
    {
        if ($context->getCustomer() === null) {
            throw CartException::customerNotLoggedIn();
        }

        try {
            $content = $this->mandateService->downloadMandate($context->getCustomer(), $mandateId, $context);
        } catch (FileNotFoundException) {
            return new Response(null, 404);
        }

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'mandate.pdf'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
