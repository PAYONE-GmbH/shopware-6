<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Paysafe;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PaysafeInvoicingController extends StorefrontController
{
    private const URL = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration?mId=';

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ConfigReaderInterface $configReader, LoggerInterface $logger)
    {
        $this->configReader = $configReader;
        $this->logger       = $logger;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/invoicing-consent", name="frontend.account.payone.paysafe.invoicing-consent", options={"seo": "false"}, methods={"GET", "POST"}, defaults={"id": null, "XmlHttpRequest": true})
     */
    public function fetchConsentModalContent(SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $companyName = $configuration->get('paysafeCompanyName');

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - paysafe company name is empty.');

            throw new NotFoundHttpException();
        }

        /** @var string $content */
        $content = (string) file_get_contents(self::URL . base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error('Could not fetch invoicing consent modal content, paysafe returned a empty response.');

            throw new NotFoundHttpException();
        }

        return new Response($content);
    }
}
