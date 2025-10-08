<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\Storefront\Controller;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Provider\Payolution\PaymentMethod\DebitPaymentMethod;
use PayonePayment\Provider\Payolution\PaymentMethod\InstallmentPaymentMethod;
use PayonePayment\Provider\Payolution\PaymentMethod\InvoicePaymentMethod;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class ConsentController extends StorefrontController
{
    private const URL = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration?mId=';

    public function __construct(
        private readonly ConfigReaderInterface $configReader,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/payone/consent',
        name: 'frontend.account.payone.payolution.consent',
        options: [
            'seo'         => false,
            'deprecation' => [
                'since'   => '6.4',
                'message' => 'The route "frontend.account.payone.payolution.consent" is deprecated and will be removed in Shopware 7.0. Use "payone.payolution.frontend.invoice.consent" instead.',
            ],
        ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'GET' ],
    )]
    #[Route(
        path: '/payone/payolution/consent',
        name: 'payone.payolution.frontend.invoice.consent',
        options: [ 'seo' => false ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'GET' ],
    )]
    public function index(SalesChannelContext $context): Response
    {
        $companyName = $this->getCompanyName($context);

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - payolution company name is empty.');

            throw $this->createNotFoundException();
        }

        $content = (string) \file_get_contents(self::URL . \base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error(
                'Could not fetch invoicing consent modal content, payolution returned an empty response.',
            );

            throw $this->createNotFoundException();
        }

        $content = (string) \strstr($content, '<header>');
        $content = \strstr($content, '</footer>', true) . '</footer>';

        return new Response($content);
    }

    protected function getCompanyName(SalesChannelContext $salesChannelContext): ?string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        return match ($salesChannelContext->getPaymentMethod()->getId()) {
            InvoicePaymentMethod::UUID     => $configuration->getString('payolutionInvoicingCompanyName'),
            InstallmentPaymentMethod::UUID => $configuration->getString('payolutionInstallmentCompanyName'),
            DebitPaymentMethod::UUID       => $configuration->getString('payolutionDebitCompanyName'),
            default                        => null,
        };
    }
}
