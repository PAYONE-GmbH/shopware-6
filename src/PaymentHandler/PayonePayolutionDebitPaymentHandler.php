<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\LineItem\LineItemDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionDebit\PayolutionDebitAuthorizeRequestFactory;
use PayonePayment\Payone\Request\PayolutionDebit\PayolutionDebitPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayonePayolutionDebitPaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayolutionDebitPreAuthorizeRequestFactory */
    private $preAuthRequestFactory;

    /** @var PayolutionDebitAuthorizeRequestFactory */
    private $authRequestFactory;

    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayolutionDebitPreAuthorizeRequestFactory $preAuthRequestFactory,
        PayolutionDebitAuthorizeRequestFactory $authRequestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        LineItemDataHandlerInterface $lineItemDataHandler
    ) {
        parent::__construct($configReader, $lineItemDataHandler);
        $this->preAuthRequestFactory = $preAuthRequestFactory;
        $this->authRequestFactory    = $authRequestFactory;
        $this->client                = $client;
        $this->translator            = $translator;
        $this->dataHandler           = $dataHandler;
    }

    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayonePayolutionDebit::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array
    {
        $currency = $transaction->getOrder()->getCurrency();
        if($parameterBag) {
            $orderLines = $parameterBag->get('orderLines');
        }

        return $this->mapPayoneOrderLines($currency, $transaction->getOrder()->getLineItems(), $orderLines);
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

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        // Get configured authorization method
        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            'payolutionDebitAuthorizationMethod',
            'preauthorization'
        );

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction);

        try {
            $this->validate($dataBag);
        } catch (PayoneRequestException $e) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

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

        // Prepare custom fields for the transaction
        $data = $this->prepareTransactionCustomFields($request, $response, array_merge(
            $this->getBaseCustomFields($response['status']),
            [
                CustomFieldInstaller::WORK_ORDER_ID      => $dataBag->get('workorder'),
                CustomFieldInstaller::CLEARING_REFERENCE => $response['addpaydata']['clearing_reference'],
                CustomFieldInstaller::CAPTURE_MODE       => 'completed',
                CustomFieldInstaller::CLEARING_TYPE      => 'fnc',
                CustomFieldInstaller::FINANCING_TYPE     => 'PYD',
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);
    }

    /**
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
