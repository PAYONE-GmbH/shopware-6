<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentHandler\InstallmentPaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\DataHandler\OrderActionLogDataHandler;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutor as StandardPaymentHandlerPayExecutor;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\Service\CartHasherService;
use PayonePayment\Service\CustomerDataPersistorService;
use PayonePayment\Service\OrderTransactionLoaderService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentHandlerPayExecutor extends StandardPaymentHandlerPayExecutor
{
    public function __construct(
        private CartHasherService $cartHasher,
        ConfigReaderInterface $configReader,
        OrderTransactionLoaderService $orderTransactionLoaderService,
        TranslatorInterface $translator,
        CustomerDataPersistorService $customerDataPersistor,
        EntityRepository $orderTransactionRepository,
        PaymentRequestEnricher $paymentRequestEnricher,
        SalesChannelContextRestorer $salesChannelContextRestorer,
        PayoneClientInterface $client,
        OrderActionLogDataHandler $orderActionLogDataHandler,
        CartService $cartService,
    ) {
        parent::__construct(
            $configReader,
            $orderTransactionLoaderService,
            $translator,
            $customerDataPersistor,
            $orderTransactionRepository,
            $paymentRequestEnricher,
            $salesChannelContextRestorer,
            $client,
            $orderActionLogDataHandler,
            $cartService,
        );
    }

    public function pay(
        PaymentHandlerInterface $paymentHandler,
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        Struct|null $validateStruct,
        AbstractDeviceFingerprintService|null $deviceFingerprintService,
    ): RedirectResponse|null {
        $criteria = new Criteria([ $transaction->getOrderTransactionId() ]);

        $criteria->addAssociation('order');
        $criteria->addAssociation(PayonePaymentOrderTransactionExtension::NAME);

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionLoaderService->getOrderTransactionWithOrder(
            $transaction->getOrderTransactionId(),
            $context,
        );

        $order = $orderTransaction?->getOrder();

        if (null === $order) {
            throw $this->createPaymentException(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $salesChannelContext = $this->salesChannelContextRestorer->restoreByOrder($order->getId(), $context);
        $dataBag             = new RequestDataBag($request->request->all());

        $this->cartHasher->validateRequest($dataBag, $transaction, $order, $salesChannelContext);

        return parent::pay(
            $paymentHandler,
            $request,
            $transaction,
            $context,
            $validateStruct,
            $deviceFingerprintService,
        );
    }
}
