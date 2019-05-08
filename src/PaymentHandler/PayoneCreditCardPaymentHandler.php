<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\CreditCard\CreditCardPreAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneCreditCardPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var CreditCardPreAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        CreditCardPreAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $transactionRepository,
        StateMachineRegistry $stateMachineRegistry,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->requestFactory        = $requestFactory;
        $this->client                = $client;
        $this->transactionRepository = $transactionRepository;
        $this->stateMachineRegistry  = $stateMachineRegistry;
        $this->translator            = $translator;
        $this->requestStack          = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $paymentTransaction = PaymentTransactionStruct::fromAsyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $this->requestStack->getCurrentRequest()->get('pseudocardpan'),
            $salesChannelContext->getContext()
        );

        $response = $this->client->request($request);

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $customFields[CustomFieldInstaller::TRANSACTION_ID]         = (string) $response['txid'];
        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER]        = 1;
        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;

        $data = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$data], $salesChannelContext->getContext());

        if (strtolower($response['status']) === 'error') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        if (strtolower($response['status']) === 'redirect') {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $state = $request->query->get('state');

        if (empty($state)) {
            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
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
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $completeState = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $salesChannelContext->getContext()
        );

        $data = [
            'id'      => $transaction->getOrderTransaction()->getId(),
            'stateId' => $completeState->getId(),
        ];

        $this->transactionRepository->update([$data], $salesChannelContext->getContext());
    }
}
