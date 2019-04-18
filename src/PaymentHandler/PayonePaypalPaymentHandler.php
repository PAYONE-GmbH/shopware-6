<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Installer\AttributeInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequest;
use PayonePayment\Payone\Request\RequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayonePaypalPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var RequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        RequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $transactionRepository,
        StateMachineRegistry $stateMachineRegistry,
        TranslatorInterface $translator
    ) {
        $this->requestFactory        = $requestFactory;
        $this->client                = $client;
        $this->transactionRepository = $transactionRepository;
        $this->stateMachineRegistry  = $stateMachineRegistry;
        $this->translator            = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, Context $context): RedirectResponse
    {
        $paymentTransaction = PaymentTransactionStruct::fromAsyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->generateRequest(
            $paymentTransaction,
            $context,
            PaypalAuthorizeRequest::class
        );

        $response = $this->client->request($request);

        if (empty($response['Status']) && $response['Status'] !== 'REDIRECT') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('test')
            );
        }

        $data = [
            'id'         => $transaction->getOrderTransaction()->getId(),
            'attributes' => [
                AttributeInstaller::TRANSACTION_ID   => (string) $response['TxId'],
                AttributeInstaller::TRANSACTION_DATA => $response,
            ],
            'payoneTransactionId' => (int) $response['TxId'],
        ];

        $this->transactionRepository->update([$data], $context);

        return new RedirectResponse($response['RedirectUrl']);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, Context $context): void
    {
        $state = $request->query->get('state');

        if (empty($state)) {
            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('test')
            );
        }

        if ($state === 'cancel') {
            throw new CustomerCanceledAsyncPaymentException(
                $transaction->getOrderTransaction()->getId(),
                ''
            );
        }

        if ($state === 'error') {
            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                'message'
            );
        }

        $completeState = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $context
        );

        $data = [
            'id'      => $transaction->getOrderTransaction()->getId(),
            'stateId' => $completeState->getId(),
        ];

        $this->transactionRepository->update([$data], $context);
    }
}
