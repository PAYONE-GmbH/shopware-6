<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Payolution;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Helper\OrderFetcher;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\RequestConstants;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PayolutionController extends StorefrontController
{
    private const URL = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration?mId=';

    public function __construct(
        private readonly ConfigReaderInterface $configReader,
        private readonly CartService $cartService,
        private readonly CartHasherInterface $cartHasher,
        private readonly PayoneClientInterface $client,
        private readonly RequestParameterFactory $requestParameterFactory,
        private readonly LoggerInterface $logger,
        private readonly OrderFetcher $orderFetcher,
        private readonly OrderConverter $orderConverter
    ) {
    }

    #[Route(path: '/payone/consent', name: 'frontend.account.payone.payolution.consent', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function displayContentModal(SalesChannelContext $context): Response
    {
        $companyName = $this->getCompanyName($context);

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - payolution company name is empty.');

            throw $this->createNotFoundException();
        }

        $content = (string) file_get_contents(self::URL . base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error('Could not fetch invoicing consent modal content, payolution returned an empty response.');

            throw $this->createNotFoundException();
        }

        $content = (string) strstr($content, '<header>');
        $content = strstr($content, '</footer>', true) . '</footer>';

        return new Response($content);
    }

    #[Route(path: '/payone/invoicing/validate', name: 'frontend.payone.payolution.invoicing.validate', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function validate(RequestDataBag $dataBag, SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $checkRequest = $this->requestParameterFactory->getRequestParameter(
            new PayolutionAdditionalActionStruct(
                $cart,
                $dataBag,
                $context,
                PayonePayolutionInvoicingPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_PAYOLUTION_PRE_CHECK
            )
        );

        try {
            $response = $this->client->request($checkRequest);
        } catch (PayoneRequestException $exception) {
            $response = [
                'status' => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }

    #[Route(path: '/payone/installment/calculation/{orderId}', name: 'frontend.payone.payolution.installment.calculation', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function calculation(RequestDataBag $dataBag, SalesChannelContext $context, ?string $orderId = null): JsonResponse
    {
        try {
            if ($orderId) {
                $order = $this->orderFetcher->getOrderById($orderId, $context->getContext());
                if (!$order instanceof OrderEntity) {
                    throw new NotFoundHttpException();
                }
                $cart = $this->orderConverter->convertToCart($order, $context->getContext());
            } else {
                $cart = $this->cartService->getCart($context->getToken(), $context);
            }

            $checkRequest = $this->requestParameterFactory->getRequestParameter(
                new PayolutionAdditionalActionStruct(
                    $cart,
                    $dataBag,
                    $context,
                    PayonePayolutionInstallmentPaymentHandler::class,
                    AbstractRequestParameterBuilder::REQUEST_ACTION_PAYOLUTION_PRE_CHECK
                )
            );

            if ($this->isPreCheckNeeded($cart, $dataBag, $context)) {
                try {
                    $response = $this->client->request($checkRequest);
                } catch (PayoneRequestException) {
                    throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
                }

                // will be used inside the calculation request
                $dataBag->set(RequestConstants::WORK_ORDER_ID, $response['workorderid']);

                $response['carthash'] = $this->cartHasher->generate($cart, $context);
            } else {
                $response = [
                    'status' => 'OK',
                    'workorderid' => $dataBag->get(RequestConstants::WORK_ORDER_ID),
                    'carthash' => $dataBag->get(RequestConstants::CART_HASH),
                ];
            }

            $calculationRequest = $this->requestParameterFactory->getRequestParameter(
                new PayolutionAdditionalActionStruct(
                    $cart,
                    $dataBag,
                    $context,
                    PayonePayolutionInstallmentPaymentHandler::class,
                    AbstractRequestParameterBuilder::REQUEST_ACTION_PAYOLUTION_CALCULATION,
                    $dataBag->get(RequestConstants::WORK_ORDER_ID)
                )
            );

            try {
                $calculationResponse = $this->client->request($calculationRequest);
            } catch (PayoneRequestException) {
                throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
            }

            $calculationResponse = $this->prepareCalculationOutput($calculationResponse);

            $response['installmentSelection'] = $this->getInstallmentSelectionHtml($calculationResponse);
            $response['calculationOverview'] = $this->getCalculationOverviewHtml($calculationResponse);

            $this->saveCalculationResponse($cart, $calculationResponse, $context);
        } catch (\Throwable $exception) {
            $response = [
                'status' => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }

    #[Route(path: '/payone/installment/download', name: 'frontend.payone.payolution.installment.download', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
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

        $url = $this->getCreditInformationUrlFromCart($cart, $duration);
        $channel = $configuration->getString(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_NAME);
        $password = $configuration->getString(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INSTALLMENT_CHANNEL_PASSWORD);

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
            $this->logger->error('Could not fetch standard credit information document for payolution installment, empty document response.', ['url' => $url]);

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

    protected function getCompanyName(SalesChannelContext $salesChannelContext): ?string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        return match ($salesChannelContext->getPaymentMethod()->getId()) {
            PayonePayolutionInvoicing::UUID => $configuration->getString('payolutionInvoicingCompanyName'),
            PayonePayolutionInstallment::UUID => $configuration->getString('payolutionInstallmentCompanyName'),
            PayonePayolutionDebit::UUID => $configuration->getString('payolutionDebitCompanyName'),
            default => null,
        };
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
            $key = str_replace('PaymentDetails_', '', (string) $key);
            $keys = explode('_', $key);

            if (\count($keys) === 4) {
                $data[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $this->convertType($keys[3], $value);

                uksort($data[$keys[0]][$keys[1]][$keys[2]], 'strcmp');
                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (\count($keys) === 3) {
                $data[$keys[0]][$keys[1]][$keys[2]] = $this->convertType($keys[2], $value);

                uksort($data[$keys[0]][$keys[1]], 'strcmp');
                uksort($data[$keys[0]], 'strcmp');
            }

            if (\count($keys) === 2) {
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
        $cartHash = $dataBag->get(RequestConstants::CART_HASH);

        if (!$this->cartHasher->validate($cart, $cartHash, $context)) {
            return true;
        }

        if (empty($dataBag->get(RequestConstants::WORK_ORDER_ID))) {
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

    /**
     * This method does return mixed. Therefore we are ignoring errors for now.
     *
     * @phpstan-ignore-next-line
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

        if (\in_array($key, $float, true)) {
            return (float) $value;
        }

        if (\in_array($key, $int, true)) {
            return (int) $value;
        }

        if (\in_array($key, $date, true)) {
            return \DateTime::createFromFormat('Y-m-d', $value);
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
