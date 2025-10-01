<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\Storefront\Controller;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class DownloadController extends StorefrontController
{
    public function __construct(
        private readonly ConfigReaderInterface $configReader,
        private readonly CartService $cartService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/payone/installment/download',
        name: 'frontend.payone.payolution.installment.download',
        options: [
            'seo' => false,
            'deprecation' => [
                'since' => '6.4',
                'message' => 'The route "frontend.payone.payolution.installment.download" is deprecated and will be removed in Shopware 7.0. Use "payone.payolution.frontend.installment.download" instead.'
            ],
        ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'GET' ],
    )]
    #[Route(
        path: '/payone/payolution/installment/download',
        name: 'payone.payolution.frontend.installment.download',
        options: [ 'seo' => false ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'GET' ],
    )]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $duration = (int) $request->get('duration');

        if (empty($duration)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, missing required duration parameter.');

            throw new UnprocessableEntityHttpException();
        }

        $cart = $this->cartService->getCart($context->getToken(), $context, false);

        if (!$cart->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, credit information missing from cart.');

            throw new UnprocessableEntityHttpException();
        }

        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $url      = $this->getCreditInformationUrlFromCart($cart, $duration);
        $channel  = $configuration->getString(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_NAME);
        $password = $configuration->getString(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_PASSWORD);

        if (empty($url) || empty($channel) || empty($password)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, please verify the channel credentials.');

            throw new UnprocessableEntityHttpException();
        }

        $streamContext = \stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . \base64_encode($channel . ':' . $password),
            ],
        ]);

        $document = \file_get_contents($url, false, $streamContext);

        if (empty($document)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, empty document response.', [ 'url' => $url ]);

            throw new UnprocessableEntityHttpException();
        }

        $response = new Response($document);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'credit-information.pdf',
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function getCreditInformationUrlFromCart(Cart $cart, int $duration): ?string
    {
        /** @var CheckoutCartPaymentData $extension */
        $extension = $cart->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);

        $calculationResponse = $extension->getCalculationResponse();

        if (empty($calculationResponse)) {
            return null;
        }

        foreach ($calculationResponse['addpaydata'] as $installment) {
            if ($installment['Duration'] === $duration) {
                return $installment['StandardCreditInformationUrl'];
            }
        }

        return null;
    }
}
