<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneDebitPaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var DebitAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        DebitAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $transactionRepository,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->requestFactory        = $requestFactory;
        $this->client                = $client;
        $this->transactionRepository = $transactionRepository;
        $this->translator            = $translator;
        $this->requestStack          = $requestStack;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromSyncPaymentTransactionStruct($transaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $this->requestStack->getCurrentRequest()->get('iban'),
            $this->requestStack->getCurrentRequest()->get('bic'),
            $this->requestStack->getCurrentRequest()->get('accountOwner'),
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

        $key = (new DateTime())->format(DATE_ATOM);

        // TODO: move custom Field handling to helper function

        $customFields = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $customFields[CustomFieldInstaller::TRANSACTION_ID]         = (string) $response['txid'];
        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER]        = 0; // TODO: as the payment is not captured yet, 0 should be ok. Needs to be verified
        $customFields[CustomFieldInstaller::USER_ID]                = $response['userid'];
        $customFields[CustomFieldInstaller::TRANSACTION_STATE]      = 'pending'; // TODO: fetch correct payment state from payone or use a alternative method
        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;

        // TODO: save $response['mandate'] data to user facing table and implement the storefront integration

        $data = [
            'id'           => $transaction->getOrderTransaction()->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$data], $salesChannelContext->getContext());

        if (strtolower($response['status']) === 'error') {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }
    }
}
