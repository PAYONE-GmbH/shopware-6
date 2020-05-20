<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionInstallmentAuthorizeRequestFactory;
use PayonePayment\Payone\Request\PayolutionInstallment\PayolutionInstallmentPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayonePayolutionInstallmentPaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayolutionInstallmentPreAuthorizeRequestFactory */
    private $preAuthRequestFactory;

    /** @var PayolutionInstallmentAuthorizeRequestFactory */
    private $authRequestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var CartHasherInterface */
    private $cartHasher;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayolutionInstallmentPreAuthorizeRequestFactory $preAuthRequestFactory,
        PayolutionInstallmentAuthorizeRequestFactory $authRequestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $lineItemRepository,
        CartHasherInterface $cartHasher
    ) {
        parent::__construct($configReader, $lineItemRepository);
        $this->preAuthRequestFactory = $preAuthRequestFactory;
        $this->authRequestFactory    = $authRequestFactory;
        $this->client                = $client;
        $this->translator            = $translator;
        $this->dataHandler           = $dataHandler;
        $this->cartHasher            = $cartHasher;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $cartHash = (string) $dataBag->get('carthash');

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $salesChannelContext)) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        // Get configured authorization method
        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            'payolutionInstallmentAuthorizationMethod',
            'authorization'
        );

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        // Select request factory based on configured authorization method
        $factory = $authorizationMethod === 'preauthorization'
            ? $this->preAuthRequestFactory
            : $this->authRequestFactory;

        $request = $factory->getRequestParameters(
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

        $data = $this->prepareTransactionCustomFields($request, $response, array_merge(
            $this->getBaseCustomFields($response['status']),
            [
                CustomFieldInstaller::WORK_ORDER_ID      => $dataBag->get('workorder'),
                CustomFieldInstaller::CLEARING_REFERENCE => $response['clearing']['Reference'],
                CustomFieldInstaller::CAPTURE_MODE       => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,
                CustomFieldInstaller::CLEARING_TYPE      => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
                CustomFieldInstaller::FINANCING_TYPE     => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYS,
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);
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
        if (strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE && (float) $transactionData['receivable'] !== 0.0) {
            return true;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }
}
