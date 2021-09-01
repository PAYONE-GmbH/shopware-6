<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\StoreApi\Response\MandateResponse;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\ContextTokenRequired;
use Shopware\Core\Framework\Routing\Annotation\Entity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

if (!class_exists('Shopware\Core\Framework\Routing\Annotation\ContextTokenRequired')) {
    include_once __DIR__ . '/../Annotation/ContextTokenRequired.php';
}

if (!class_exists('Shopware\Core\Framework\Routing\Annotation\Entity')) {
    include_once __DIR__ . '/../Annotation/Entity.php';
}

/**
 * @IgnoreAnnotation("ContextTokenRequired")
 * @IgnoreAnnotation("Entity")
 * @RouteScope(scopes={"store-api"})
 * @ContextTokenRequired
 */
class MandateRoute extends AbstractMandateRoute
{
    /** @var MandateServiceInterface */
    private $mandateService;

    public function __construct(MandateServiceInterface $mandateService)
    {
        $this->mandateService = $mandateService;
    }

    public function getDecorated(): AbstractCardRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Entity("payone_payment_mandate")
     * @Route("/store-api/payone/account/mandate", name="store-api.payone.account.mandate", methods={"GET"})
     */
    public function load(SalesChannelContext $context): MandateResponse
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            throw new CustomerNotLoggedInException();
        }

        $result = $this->mandateService->getMandates($customer, $context);

        return new MandateResponse($result);
    }

    /**
     * @Entity("payone_payment_mandate")
     * @Route("/store-api/payone/account/mandate/{mandateId}", name="store-api.payone.account.mandate.file", methods={"GET"})
     */
    public function getFile(string $mandateId, SalesChannelContext $context): Response
    {
        if (null === $context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        try {
            $content = $this->mandateService->downloadMandate($context->getCustomer(), $mandateId, $context);
        } catch (FileNotFoundException $e) {
            return new Response(null, 404);
        }

        $response = new Response($content ?? '');

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'mandate.pdf'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
