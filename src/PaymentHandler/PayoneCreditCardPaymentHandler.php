<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\CreditCard\CreditCardPreAuthorizeRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneCreditCardPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var CreditCardPreAuthorizeRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var PaymentStateHandlerInterface */
    private $stateHandler;

    /** @var CardRepositoryInterface */
    private $cardRepository;

    public function __construct(
        CreditCardPreAuthorizeRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler,
        CardRepositoryInterface $cardRepository
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->translator     = $translator;
        $this->dataHandler    = $dataHandler;
        $this->stateHandler   = $stateHandler;
        $this->cardRepository = $cardRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $paymentTransaction = PaymentTransaction::fromAsyncPaymentTransactionStruct($transaction);

        $pseudoCardPan      = $dataBag->get('pseudoCardPan');
        $savedPseudoCardPan = $dataBag->get('savedPseudoCardPan');
        $truncatedCardPan   = $dataBag->get('truncatedCardPan');

        if (!empty($savedPseudoCardPan)) {
            $pseudoCardPan = $savedPseudoCardPan;
        }

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $pseudoCardPan,
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

        $data = [
            CustomFieldInstaller::LAST_REQUEST      => $request['request'],
            CustomFieldInstaller::TRANSACTION_ID    => (string) $response['txid'],
            CustomFieldInstaller::TRANSACTION_STATE => $response['status'],
            CustomFieldInstaller::SEQUENCE_NUMBER   => -1,
            CustomFieldInstaller::USER_ID           => $response['userid'],
            CustomFieldInstaller::ALLOW_CAPTURE     => false,
            CustomFieldInstaller::ALLOW_REFUND      => false,
        ];

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), $response);

        if (empty($savedPseudoCardPan)) {
            $this->cardRepository->saveCard(
                $salesChannelContext->getCustomer(),
                $truncatedCardPan,
                $pseudoCardPan,
                $salesChannelContext->getContext()
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
        $this->stateHandler->handleStateResponse($transaction, (string) $request->query->get('state'));
    }
}
