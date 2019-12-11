<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionDebit\PayolutionDebitPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayonePayolutionDebitPaymentHandler implements SynchronousPaymentHandlerInterface, PayonePaymentHandlerInterface
{
    /** @var PayolutionDebitPreAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        PayolutionDebitPreAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler
    )
    {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
        $this->translator = $translator;
        $this->dataHandler = $dataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
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
        if (strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE && (float)$transactionData['receivable'] !== 0.0) {
            return true;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction);

        try {
            $this->validate($dataBag);
        } catch (PayoneRequestException $e) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $dataBag,
            $salesChannelContext
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage']
            );
        } catch (Throwable $exception) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }
        if (empty($response['status']) || $response['status'] === 'ERROR') {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $data = [
            CustomFieldInstaller::LAST_REQUEST => $request['request'],
            CustomFieldInstaller::TRANSACTION_ID => (string)$response['txid'],
            CustomFieldInstaller::TRANSACTION_STATE => $response['status'],
            CustomFieldInstaller::AUTHORIZATION_TYPE => $request['request'],
            CustomFieldInstaller::SEQUENCE_NUMBER => -1,
            CustomFieldInstaller::USER_ID => $response['userid'],
            CustomFieldInstaller::ALLOW_CAPTURE => false,
            CustomFieldInstaller::ALLOW_REFUND => false,
            CustomFieldInstaller::WORK_ORDER_ID => $dataBag->get('workorder'),
            CustomFieldInstaller::CLEARING_REFERENCE => $response['addpaydata']['clearing_reference'],
            CustomFieldInstaller::CAPTURE_MODE => 'completed',
            CustomFieldInstaller::CLEARING_TYPE => 'fnc',
            CustomFieldInstaller::FINANCING_TYPE => 'PYD',
        ];

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $response);
    }

    /**
     * @param RequestDataBag $dataBag
     * @throws PayoneRequestException
     */
    private function validate(RequestDataBag $dataBag)
    {
        if ($dataBag->get('payolutionConsent') !== 'on') {
            throw new PayoneRequestException('No payolutionConsent');
        }
        if ($dataBag->get('payolutionMandate') !== 'on') {
            throw new PayoneRequestException('No payolutionMandate');
        }
    }
}
