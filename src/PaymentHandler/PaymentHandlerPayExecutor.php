<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\DataHandler\OrderActionLogDataHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\Service\CustomerDataPersistorService;
use PayonePayment\Service\OrderTransactionLoaderService;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentHandlerPayExecutor implements PaymentHandlerPayExecutorInterface
{
    use CreatePaymentExceptionTrait;

    public function __construct(
        protected ConfigReaderInterface $configReader,
        protected OrderTransactionLoaderService $orderTransactionLoaderService,
        protected TranslatorInterface $translator,
        protected CustomerDataPersistorService $customerDataPersistor,
        protected EntityRepository $orderTransactionRepository,
        protected PaymentRequestEnricher $paymentRequestEnricher,
        protected SalesChannelContextRestorer $salesChannelContextRestorer,
        protected PayoneClientInterface $client,
        protected OrderActionLogDataHandler $orderActionLogDataHandler,
        protected CartService $cartService,
    ) {
    }

    public function pay(
        PaymentHandlerInterface $paymentHandler,
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        Struct|null $validateStruct,
        AbstractDeviceFingerprintService|null $deviceFingerprintService,
    ): RedirectResponse|null {
        try {
            $dataBag = new RequestDataBag($request->request->all());

            $paymentHandler->validateRequestData($dataBag);
        } catch (PayoneRequestException) {
            $deviceFingerprintService?->deleteDeviceIdentToken();

            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionLoaderService->getOrderTransactionWithOrder(
            $transaction->getOrderTransactionId(),
            $context,
        );

        $order = $orderTransaction?->getOrder();

        if (!$order instanceof OrderEntity) {
            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $this->customerDataPersistor->save($order, $dataBag, $context);

        $paymentTransaction = PaymentTransactionDto::createFromPaymentTransaction(
            $transaction,
            $order,
            $orderTransaction,
        );

        $authorizationMethod = $this->getAuthorizationMethod(
            $order->getSalesChannelId(),
            $paymentHandler->getConfigKeyPrefix() . 'AuthorizationMethod',
            $paymentHandler->getDefaultAuthorizationMethod(),
        );

        $salesChannelContext = $this->salesChannelContextRestorer->restoreByOrder($order->getId(), $context);
        $cart                = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $paymentRequestDto = new PaymentRequestDto(
            $paymentTransaction,
            $dataBag,
            $salesChannelContext,
            $cart,
            $paymentHandler,
            $authorizationMethod,
        );

        $payoneRequest = $this->paymentRequestEnricher->enrich(
            $paymentRequestDto,
            $paymentHandler->getRequestEnricherChain(),
        );

        try {
            $response = $this->client->request($payoneRequest->all());
        } catch (PayoneRequestException|\Throwable $exception) {
            $deviceFingerprintService?->deleteDeviceIdentToken();

            $message = $exception instanceof PayoneRequestException
                ? $exception->getResponse()['error']['CustomerMessage']
                : $this->translator->trans('PayonePayment.errorMessages.genericError')
            ;

            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $message,
            );
        }

        $paymentHandler->getResponseHandler()->handle(
            $transaction->getOrderTransactionId(),
            PaymentTransaction::fromOrderTransaction($orderTransaction, $order),
            $dataBag,
            $payoneRequest->all(),
            $response,
            $salesChannelContext,
        );

        $this->orderActionLogDataHandler->createOrderActionLog(
            $order,
            $payoneRequest->all(),
            $response,
            $salesChannelContext->getContext(),
        );

        $deviceFingerprintService?->deleteDeviceIdentToken();

        return $paymentHandler->getRedirectResponse($salesChannelContext, $payoneRequest->all(), $response);
    }

    protected function getAuthorizationMethod(string $salesChannelId, string $configKey, string $default): string
    {
        return $this->configReader->read($salesChannelId)->getString($configKey, $default);
    }
}
