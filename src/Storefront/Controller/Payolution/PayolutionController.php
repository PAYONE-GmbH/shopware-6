<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Payolution;

use LogicException;
use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionCalculationRequestFactory;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionPreCheckRequestFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

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

    /** @var PayolutionPreCheckRequestFactory */
    private $preCheckRequestFactory;

    /** @var PayolutionCalculationRequestFactory */
    private $calculationRequestFactory;

    /** @var RequestStack */
    private $requestStack;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigReaderInterface $configReader,
        CartService $cartService,
        CartHasherInterface $cartHasher,
        PayoneClientInterface $client,
        PayolutionPreCheckRequestFactory $preCheckRequestFactory,
        PayolutionCalculationRequestFactory $calculationRequestFactory,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->configReader              = $configReader;
        $this->cartService               = $cartService;
        $this->cartHasher                = $cartHasher;
        $this->client                    = $client;
        $this->preCheckRequestFactory    = $preCheckRequestFactory;
        $this->calculationRequestFactory = $calculationRequestFactory;
        $this->requestStack = $requestStack;
        $this->logger                    = $logger;
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
     * @Route("/payone/payolution/calculation", name="frontend.account.payone.payolution.calculation", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function calculation(SalesChannelContext $context, RequestDataBag $dataBag): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart($context->getToken(), $context);

            $checkRequest = $this->preCheckRequestFactory->getRequestParameters($cart, $dataBag, $context);

            if ($this->isPreCheckNeeded($cart, $dataBag, $context)) {
                try {
                    $response = $this->client->request($checkRequest);
                } catch (PayoneRequestException $exception) {
                    throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
                }

                // will be used inside the calculation request
                $dataBag->set('workorder', $response['workorderid']);

                $response['carthash'] = $this->cartHasher->generate($cart, $context);
            } else {
                $response = [
                    'status'      => 'OK',
                    'workorderid' => $dataBag->get('workorder'),
                    'carthash'    => $dataBag->get('carthash'),
                ];
            }

            $calculationRequest = $this->calculationRequestFactory->getRequestParameters($cart, $dataBag, $context);

            try {
                $calculationResponse = $this->client->request($calculationRequest);
            } catch (PayoneRequestException $exception) {
                throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
            }

            $calculationResponse = $this->prepareCalculationOutput($calculationResponse);

            $response['installmentSelection'] = $this->getInstallmentSelectionHtml($calculationResponse);
            $response['calculationOverview'] = $this->geCalculationOverviewHtml($calculationResponse);
        } catch (Throwable $exception) {
            $response = [
                'status' => 'ERROR',
                'message' => $exception->getMessage()
            ];
        }

        return new JsonResponse($response);
    }

    private function prepareCalculationOutput(array $response): array
    {
        $data = [];

        foreach ($response['addpaydata'] as $key => $value) {
            $key = str_replace('PaymentDetails_', '', $key);
            $keys = explode('_', $key);

            if (count($keys) === 4) {
                $data[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $value;

                uksort($data[$keys[0]][$keys[1]][$keys[2]], 'strcmp');
                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (count($keys) === 3) {
                $data[$keys[0]][$keys[1]][$keys[2]] = $value;

                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (count($keys) === 2) {
                $data[$keys[0]][$keys[1]] = $value;

                uksort($data[$keys[0]], 'strcmp');
            }
        }

        uksort($data, 'strcmp');

        $response['addpaydata'] = [];

        foreach ($data as $element) {
            if (!empty($element['Installment'])) {
                $element['Installment'] = array_values($element['Installment']);
            }

            $response['addpaydata'][] = $element;
        }

        return $response;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/download", name="frontend.account.payone.payolution.download", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function download(SalesChannelContext $context): Response
    {
        // TODO: implement download controller for payment plan

        return new Response();
    }

    private function isPreCheckNeeded(Cart $cart, RequestDataBag $dataBag, SalesChannelContext $context)
    {
        $cartHash = $dataBag->get('carthash');

        if (!$this->cartHasher->validate($cart, $cartHash, $context)) {
            return true;
        }

        if (empty($dataBag->get('workorder'))) {
            return true;
        }

        return false;
    }

    private function getInstallmentSelectionHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/payone/payolution/payolution-installment-selection.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    private function geCalculationOverviewHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/payone/payolution/payolution-calculation-overview.html.twig';

        return $this->renderView($view, $calculationResponse);
    }
}
