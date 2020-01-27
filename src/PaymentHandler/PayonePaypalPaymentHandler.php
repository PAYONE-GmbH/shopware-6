<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayonePaypalPaymentHandler implements AsynchronousPaymentHandlerInterface, PayonePaymentHandlerInterface
{
    /** @var PaypalAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var PaymentStateHandlerInterface */
    private $stateHandler;

    public function __construct(
        PaypalAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->translator     = $translator;
        $this->dataHandler    = $dataHandler;
        $this->stateHandler   = $stateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $paymentTransaction = PaymentTransaction::fromAsyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $dataBag,
            $salesChannelContext
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

        if (empty($response['status']) || $response['status'] === 'ERROR') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $data = [
            CustomFieldInstaller::LAST_REQUEST       => $request['request'],
            CustomFieldInstaller::TRANSACTION_ID     => (string) $response['txid'],
            CustomFieldInstaller::TRANSACTION_STATE  => $response['status'],
            CustomFieldInstaller::AUTHORIZATION_TYPE => $request['request'],
            CustomFieldInstaller::SEQUENCE_NUMBER    => -1,
            CustomFieldInstaller::USER_ID            => $response['userid'],
            CustomFieldInstaller::ALLOW_CAPTURE      => false,
            CustomFieldInstaller::ALLOW_REFUND       => false,
        ];

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $response);

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
        $this->stateHandler->handleStateResponse($transaction, (string) $request->query->get('state'));
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if (!array_key_exists(CustomFieldInstaller::AUTHORIZATION_TYPE, $customFields)) {
            return false;
        }

        if ($customFields[CustomFieldInstaller::AUTHORIZATION_TYPE] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_APPOINTED
            && strtolower($transactionData['transaction_status']) === TransactionStatusService::STATUS_COMPLETED;
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE && (float) $transactionData['receivable'] !== 0.0) {
            return true;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }
}
