<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\DataHandler\OrderActionLogDataHandler;
use PayonePayment\PaymentHandler\CreatePaymentExceptionTrait;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentHandlerPayExecutor implements PaymentHandlerPayExecutorInterface
{
    use CreatePaymentExceptionTrait;

    private Serializer $serializer;

    public function __construct(
        private ConfigReaderInterface $configReader,
        private TranslatorInterface $translator,
        private EntityRepository $orderTransactionRepository,
        private SalesChannelContextRestorer $salesChannelContextRestorer,
        private OrderActionLogDataHandler $orderActionLogDataHandler,
    ) {
        $this->serializer = new Serializer(encoders: [ new JsonEncoder() ]);
    }

    #[\Override]
    public function pay(
        PaymentHandlerInterface $paymentHandler,
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        Struct|null $validateStruct,
        AbstractDeviceFingerprintService|null $deviceFingerprintService,
    ): RedirectResponse|null {
        $dataBag  = new RequestDataBag($request->request->all());
        $response = $this->serializer->decode((string) $dataBag->get('response', '{}'), JsonEncoder::FORMAT);

        if (
            null === $response
            || !\array_key_exists('status', $response)
            || !\array_key_exists('txid', $response)
        ) {
            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        if ('APPROVED' !== $response['status'] && 'PENDING' !== $response['status']) {
            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $criteria = new Criteria([ $transaction->getOrderTransactionId() ]);

        $criteria->addAssociation('order');
        $criteria->addAssociation(PayonePaymentOrderTransactionExtension::NAME);

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        $order            = $orderTransaction?->getOrder();

        if (!$order instanceof OrderEntity) {
            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $authorizationMethod = $this->getAuthorizationMethod(
            $order->getSalesChannelId(),
            $paymentHandler->getConfigKeyPrefix() . 'AuthorizationMethod',
            $paymentHandler->getDefaultAuthorizationMethod(),
        );

        $salesChannelContext = $this->salesChannelContextRestorer->restoreByOrder($order->getId(), $context);
        $payoneRequest       = [ 'request' => $authorizationMethod ];

        $paymentHandler->getResponseHandler()->handle(
            $transaction->getOrderTransactionId(),
            PaymentTransaction::fromOrderTransaction($orderTransaction, $order),
            $dataBag,
            $payoneRequest,
            $response,
            $salesChannelContext,
        );

        // special case: the request has been already processed before the payment handler has been executed. Now we will log the previous request/response
        $this->orderActionLogDataHandler->createOrderActionLog(
            $order,
            $payoneRequest,
            $response,
            $salesChannelContext->getContext(),
        );

        return $paymentHandler->getRedirectResponse($salesChannelContext, $payoneRequest, $response);
    }

    protected function getAuthorizationMethod(string $salesChannelId, string $configKey, string $default): string
    {
        return $this->configReader->read($salesChannelId)->getString($configKey, $default);
    }
}
