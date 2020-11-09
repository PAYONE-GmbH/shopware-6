<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use LogicException;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequestFactory;
use PayonePayment\Payone\Request\Debit\DebitPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneDebitPaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;
    /** @var DebitPreAuthorizeRequestFactory */
    private $preAuthRequestFactory;

    /** @var DebitAuthorizeRequestFactory */
    private $authRequestFactory;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var MandateServiceInterface */
    private $mandateService;

    public function __construct(
        ConfigReaderInterface $configReader,
        DebitPreAuthorizeRequestFactory $preAuthRequestFactory,
        DebitAuthorizeRequestFactory $authRequestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $lineItemRepository,
        MandateServiceInterface $mandateService,
        RequestStack $requestStack
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);
        $this->preAuthRequestFactory = $preAuthRequestFactory;
        $this->authRequestFactory    = $authRequestFactory;
        $this->client                = $client;
        $this->translator            = $translator;
        $this->dataHandler           = $dataHandler;
        $this->mandateService        = $mandateService;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $requestData = $this->fetchRequestData();

        // Get configured authorization method
        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            'debitAuthorizationMethod',
            'authorization'
        );

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        // Select request factory based on configured authorization method
        $factory = $authorizationMethod === 'preauthorization'
            ? $this->preAuthRequestFactory
            : $this->authRequestFactory;

        $request = $factory->getRequestParameters(
            $paymentTransaction,
            $requestData,
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

        $data = $this->prepareTransactionCustomFields($request, $response, array_merge(
            $this->getBaseCustomFields($response['status']),
            [
                CustomFieldInstaller::TRANSACTION_STATE      => AbstractPayonePaymentHandler::PAYONE_STATE_PENDING,
                CustomFieldInstaller::MANDATE_IDENTIFICATION => $response['mandate']['Identification'],
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);

        $date = DateTime::createFromFormat('Ymd', $response['mandate']['DateOfSignature']);

        if (empty($date)) {
            throw new LogicException('could not parse sepa mandate signature date');
        }

        if (null !== $salesChannelContext->getCustomer()) {
            $this->mandateService->saveMandate(
                $salesChannelContext->getCustomer(),
                $response['mandate']['Identification'],
                $date,
                $salesChannelContext
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverCapturable($transactionData, $customFields)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_APPOINTED) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData, $customFields);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverRefundable($transactionData, $customFields)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData, $customFields);
    }
}
