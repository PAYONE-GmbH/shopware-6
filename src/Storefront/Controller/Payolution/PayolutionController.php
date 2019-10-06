<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Payolution;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionPreCheckRequestFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayolutionController extends StorefrontController
{
    private const URL = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration?mId=';

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var CartService */
    private $cartService;

    /** @var CartHasherInterface */
    private $cartHasher;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PayolutionPreCheckRequestFactory */
    private $preCheckRequestFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigReaderInterface $configReader,
        CartService $cartService,
        CartHasherInterface $cartHasher,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        PayolutionPreCheckRequestFactory $preCheckRequestFactory,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->cartService = $cartService;
        $this->cartHasher = $cartHasher;
        $this->client = $client;
        $this->translator = $translator;
        $this->preCheckRequestFactory = $preCheckRequestFactory;
        $this->logger       = $logger;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/consent", name="frontend.account.payone.payolution.consent", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function displayContentModal(SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $companyName = '';

        if ($context->getPaymentMethod()->getId() === PayonePayolutionInvoicing::UUID) {
            $companyName = $configuration->get('payolutionInvoicingCompanyName');
        }

        if ($context->getPaymentMethod()->getId() === PayonePayolutionInstallment::UUID) {
            $companyName = $configuration->get('payolutionInstallmentCompanyName');
        }

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - payolution company name is empty.');

            throw new NotFoundHttpException();
        }

        /** @var string $content */
        $content = (string) file_get_contents(self::URL . base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error('Could not fetch invoicing consent modal content, payolution returned a empty response.');

            throw new NotFoundHttpException();
        }

        $content = strstr($content, '<header>');
        $content = strstr($content, '</footer>', true) . '</footer>';

        return new Response($content);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/preCheck", name="frontend.account.payone.payolution.precheck", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function preCheck(SalesChannelContext $context, RequestDataBag $dataBag): Response
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $request = $this->preCheckRequestFactory->getRequestParameters($cart, $dataBag, $context);

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new RuntimeException($this->translator->trans('PayonePayment.errorMessages.genericError'));
        }

        $response['cartHash'] = $this->cartHasher->generate($cart, $context);

        // TODO: call calculation endpoint and retrieve plans
        // TODO: remove calculation route
        // TODO: rename preCheck to something meaningfull
        //

        return new Response(json_encode($response, JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/calculation", name="frontend.account.payone.payolution.calculation", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function calculation(SalesChannelContext $context): Response
    {
        return new Response();
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/download", name="frontend.account.payone.payolution.download", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function download(SalesChannelContext $context): Response
    {
        return new Response();
    }
}
