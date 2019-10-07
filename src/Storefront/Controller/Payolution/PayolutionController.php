<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Payolution;

use DateTime;
use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionCalculationRequestFactory;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionPreCheckRequestFactory;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    /** @var PayolutionPreCheckRequestFactory */
    private $preCheckRequestFactory;

    /** @var PayolutionCalculationRequestFactory */
    private $calculationRequestFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigReaderInterface $configReader,
        CartService $cartService,
        CartHasherInterface $cartHasher,
        PayoneClientInterface $client,
        PayolutionPreCheckRequestFactory $preCheckRequestFactory,
        PayolutionCalculationRequestFactory $calculationRequestFactory,
        LoggerInterface $logger
    ) {
        $this->configReader              = $configReader;
        $this->cartService               = $cartService;
        $this->cartHasher                = $cartHasher;
        $this->client                    = $client;
        $this->preCheckRequestFactory    = $preCheckRequestFactory;
        $this->calculationRequestFactory = $calculationRequestFactory;
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

        $content = (string) strstr($content, '<header>');
        $content = (string) strstr($content, '</footer>', true) . '</footer>';

        return new Response($content);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/payolution/calculation", name="frontend.account.payone.payolution.calculation", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function calculation(RequestDataBag $dataBag, SalesChannelContext $context): JsonResponse
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
            $response['calculationOverview']  = $this->geCalculationOverviewHtml($calculationResponse);
            
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
     * @Route("/payone/payolution/download", name="frontend.account.payone.payolution.download", options={"seo": "false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function download(Request $request, SalesChannelContext $context): Response
    {
        $duration = (int) $request->get('duration');

        if (empty($duration)) {
            throw new UnprocessableEntityHttpException();
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        if (!$cart->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            throw new UnprocessableEntityHttpException();
        }

        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $url = $this->getCreditInformationUrlFromCart($cart, $duration);
        $channel = $configuration->get('payolutionInstallmentChannelName');
        $password = $configuration->get('payolutionInstallmentChannelPassword');

        if (empty($url) || empty($channel) || empty($password)) {
            $this->logger->error('Could not fetch standard credit information document for payolution installment, please verify the channel credentials.');

            throw new UnprocessableEntityHttpException();
        }

        $streamContext = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . base64_encode($channel . ':' . $password)
            ]
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
        $view = '@PayonePayment/payone/payolution/payolution-installment-selection.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    private function geCalculationOverviewHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/payone/payolution/payolution-calculation-overview.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
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
            'calculationResponse'    => $calculationResponse,
        ]));

        $cart->addExtension(CheckoutCartPaymentData::EXTENSION_NAME, $cartData);

        $this->cartService->recalculate($cart, $context);
    }
}
