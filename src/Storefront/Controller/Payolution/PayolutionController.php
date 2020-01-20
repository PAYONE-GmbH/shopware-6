<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Payolution;

use DateTime;
use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionInstallmentCalculationRequestFactory;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionInstallmentPreCheckRequestFactory;
use PayonePayment\Payone\Request\PayolutionInvoicing\PayolutionInvoicingPreCheckRequestFactory;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

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

    /** @var PayolutionInvoicingPreCheckRequestFactory */
    private $invoicingPreCheckRequestFactory;

    /** @var PayolutionInstallmentPreCheckRequestFactory */
    private $installmentPreCheckRequestFactory;

    /** @var PayolutionInstallmentCalculationRequestFactory */
    private $installmentCalculationRequestFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigReaderInterface $configReader,
        CartService $cartService,
        CartHasherInterface $cartHasher,
        PayoneClientInterface $client,
        PayolutionInvoicingPreCheckRequestFactory $invoicingPreCheckRequestFactory,
        PayolutionInstallmentPreCheckRequestFactory $installmentPreCheckRequestFactory,
        PayolutionInstallmentCalculationRequestFactory $installmentCalculationRequestFactory,
        LoggerInterface $logger
    ) {
        $this->configReader                         = $configReader;
        $this->cartService                          = $cartService;
        $this->cartHasher                           = $cartHasher;
        $this->client                               = $client;
        $this->invoicingPreCheckRequestFactory      = $invoicingPreCheckRequestFactory;
        $this->installmentPreCheckRequestFactory    = $installmentPreCheckRequestFactory;
        $this->installmentCalculationRequestFactory = $installmentCalculationRequestFactory;
        $this->logger                               = $logger;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/consent", name="frontend.account.payone.payolution.consent", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function displayContentModal(SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        switch ($context->getPaymentMethod()->getId()) {
            case PayonePayolutionInvoicing::UUID:
                $companyName = $configuration->get('payolutionInvoicingCompanyName');

                break;

            case PayonePayolutionInstallment::UUID:
                $companyName = $configuration->get('payolutionInstallmentCompanyName');

                break;

            case PayonePayolutionDebit::UUID:
                $companyName = $configuration->get('payolutionDebitCompanyName');

                break;

            default:
                $companyName = null;

                break;
        }

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - payolution company name is empty.');

            throw $this->createNotFoundException();
        }

        /** @var string $content */
        $content = (string) file_get_contents(self::URL . base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error('Could not fetch invoicing consent modal content, payolution returned an empty response.');

            throw $this->createNotFoundException();
        }

        $content = (string) strstr($content, '<header>');
        $content = (string) strstr($content, '</footer>', true) . '</footer>';

        return new Response($content);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/invoicing/validate", name="frontend.payone.payolution.invoicing.validate", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function validate(RequestDataBag $dataBag, SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $checkRequest = $this->invoicingPreCheckRequestFactory->getRequestParameters($cart, $dataBag, $context);

        try {
            $response = $this->client->request($checkRequest);
        } catch (PayoneRequestException $exception) {
            $response = [
                'status'  => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/installment/calculation", name="frontend.payone.payolution.installment.calculation", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function calculation(RequestDataBag $dataBag, SalesChannelContext $context): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart($context->getToken(), $context);

            $checkRequest = $this->installmentPreCheckRequestFactory->getRequestParameters($cart, $dataBag, $context);

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

            $calculationRequest = $this->installmentCalculationRequestFactory->getRequestParameters($cart, $dataBag, $context);

            try {
                $calculationResponse = $this->client->request($calculationRequest);
            } catch (PayoneRequestException $exception) {
                throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
            }

            $calculationResponse = $this->prepareCalculationOutput($calculationResponse);

            $response['installmentSelection'] = $this->getInstallmentSelectionHtml($calculationResponse);
            $response['calculationOverview']  = $this->getCalculationOverviewHtml($calculationResponse);

            $this->saveCalculationResponse($cart, $calculationResponse, $context);
        } catch (Throwable $exception) {
            $response = [
                'status'  => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/installment/download", name="frontend.payone.payolution.installment.download", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function download(Request $request, SalesChannelContext $context): Response
    {
        $duration = (int) $request->get('duration');

        if (empty($duration)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, missing required duration parameter.');

            throw new UnprocessableEntityHttpException();
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        if (!$cart->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, credit information missing from cart.');

            throw new UnprocessableEntityHttpException();
        }

        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $url      = $this->getCreditInformationUrlFromCart($cart, $duration);
        $channel  = $configuration->get('payolutionInstallmentChannelName');
        $password = $configuration->get('payolutionInstallmentChannelPassword');

        if (empty($url) || empty($channel) || empty($password)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, please verify the channel credentials.');

            throw new UnprocessableEntityHttpException();
        }

        $streamContext = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . base64_encode($channel . ':' . $password),
            ],
        ]);

        $document = file_get_contents($url, false, $streamContext);

        if (empty($document)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, empty document response.');

            throw new UnprocessableEntityHttpException();
        }

        $response = new Response($document);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'credit-information.pdf'
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

    private function prepareCalculationOutput(array $response): array
    {
        $data = [];

        foreach ($response['addpaydata'] as $key => $value) {
            $key  = str_replace('PaymentDetails_', '', $key);
            $keys = explode('_', $key);

            if (count($keys) === 4) {
                $data[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $this->convertType($keys[3], $value);

                uksort($data[$keys[0]][$keys[1]][$keys[2]], 'strcmp');
                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (count($keys) === 3) {
                $data[$keys[0]][$keys[1]][$keys[2]] = $this->convertType($keys[2], $value);

                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (count($keys) === 2) {
                $data[$keys[0]][$keys[1]] = $this->convertType($keys[1], $value);

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

    private function isPreCheckNeeded(Cart $cart, RequestDataBag $dataBag, SalesChannelContext $context): bool
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
        $view = '@PayonePayment/storefront/payone/payolution/payolution-installment-selection.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    private function getCalculationOverviewHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/storefront/payone/payolution/payolution-calculation-overview.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    private function convertType(string $key, $value)
    {
        $float = [
            'Amount',
            'InterestRate',
            'OriginalAmount',
            'TotalAmount',
            'EffectiveInterestRate',
            'MinimumInstallmentFee',
        ];

        $int = [
            'Duration',
        ];

        $date = [
            'Due',
        ];

        if (in_array($key, $float)) {
            return (float) $value;
        }

        if (in_array($key, $int)) {
            return (int) $value;
        }

        if (in_array($key, $date)) {
            return DateTime::createFromFormat('Y-m-d', $value);
        }

        return $value;
    }

    private function saveCalculationResponse(Cart $cart, array $calculationResponse, SalesChannelContext $context): void
    {
        $cartData = new CheckoutCartPaymentData();

        $cartData->assign(array_filter([
            'calculationResponse' => $calculationResponse,
        ]));

        $cart->addExtension(CheckoutCartPaymentData::EXTENSION_NAME, $cartData);

        $this->cartService->recalculate($cart, $context);
    }
}
