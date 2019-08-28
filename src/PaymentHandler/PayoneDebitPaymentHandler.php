<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneDebitPaymentHandler implements SynchronousPaymentHandlerInterface, PayonePaymentHandlerInterface
{
    /** @var DebitAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var MandateServiceInterface */
    private $mandateService;

    public function __construct(
        DebitAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        MandateServiceInterface $mandateService
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->translator     = $translator;
        $this->dataHandler    = $dataHandler;
        $this->mandateService = $mandateService;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction);

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

        $data = [
            CustomFieldInstaller::LAST_REQUEST           => $request['request'],
            CustomFieldInstaller::TRANSACTION_ID         => (string) $response['txid'],
            CustomFieldInstaller::TRANSACTION_STATE      => 'pending',
            CustomFieldInstaller::AUTHORIZATION_TYPE     => $request['request'],
            CustomFieldInstaller::SEQUENCE_NUMBER        => -1,
            CustomFieldInstaller::USER_ID                => $response['userid'],
            CustomFieldInstaller::ALLOW_CAPTURE          => false,
            CustomFieldInstaller::ALLOW_REFUND           => false,
            CustomFieldInstaller::MANDATE_IDENTIFICATION => $response['mandate']['Identification'],
        ];

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $response);

        $date = DateTime::createFromFormat('Ymd', $response['mandate']['DateOfSignature']);

        $this->mandateService->saveMandate(
            $salesChannelContext->getCustomer(),
            $response['mandate']['Identification'],
            $date,
            $salesChannelContext
        );
    }

    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if ($customFields[CustomFieldInstaller::AUTHORIZATION_TYPE] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_APPOINTED;
    }

    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE && (float) $transactionData['receivable'] !== 0.0) {
            return true;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }
}
