<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\CreditCard\CreditCardPreAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
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
use Throwable;

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
     *
     * TODO: Refaktor payment handlers into one generic payment handler for all payment methods
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $paymentTransaction = PaymentTransactionStruct::fromAsyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $this->requestStack->getCurrentRequest()->get('pseudocardpan'),
            $salesChannelContext->getContext()
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage']
            );
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $customFields[CustomFieldInstaller::TRANSACTION_ID]         = (string) $response['txid'];
        $customFields[CustomFieldInstaller::TRANSACTION_STATE]      = $response['status'];
        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER]        = 0; // TODO: as the payment is not captured yet, 0 should be ok. Needs to be verified
        $customFields[CustomFieldInstaller::USER_ID]                = $response['userid'];
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
     *
     * TODO: Move finalize to generic handler
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
    }
}
