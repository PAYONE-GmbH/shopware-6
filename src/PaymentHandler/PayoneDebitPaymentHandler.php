<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneDebitPaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var DebitAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        DebitAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->translator     = $translator;
        $this->dataHandler    = $dataHandler;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromSyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $dataBag,
            $salesChannelContext->getContext()
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
            CustomFieldInstaller::TRANSACTION_ID    => (string) $response['txid'],
            CustomFieldInstaller::TRANSACTION_STATE => 'pending',
            CustomFieldInstaller::SEQUENCE_NUMBER   => -1,
            CustomFieldInstaller::USER_ID           => $response['userid'],
        ];

        $this->dataHandler->saveTransactionData($salesChannelContext, $paymentTransaction, $data);
        $this->dataHandler->logResponse($salesChannelContext, $paymentTransaction, $response);
    }
}
