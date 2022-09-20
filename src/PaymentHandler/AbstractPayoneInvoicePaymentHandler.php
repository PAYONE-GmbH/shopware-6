<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class AbstractPayoneInvoicePaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $lineItemRepository,
        RequestStack $requestStack,
        RequestParameterFactory $requestParameterFactory
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);

        $this->client                  = $client;
        $this->translator              = $translator;
        $this->dataHandler             = $dataHandler;
        $this->requestParameterFactory = $requestParameterFactory;
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
            $this->getConfigKey(),
            'preauthorization'
        );

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        $request = $this->requestParameterFactory->getRequestParameter(
            new PaymentTransactionStruct(
                $paymentTransaction,
                $requestData,
                $salesChannelContext,
                $this->getPaymentMethod(),
                $authorizationMethod
            )
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
                CustomFieldInstaller::CAPTURE_MODE => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,

                // Set clearing type explicitly
                CustomFieldInstaller::CLEARING_TYPE => static::PAYONE_CLEARING_REC,

                // Store clearing bank account information as custom field of the transaction in order to
                // use this data for payment instructions of an invoice or similar.
                // See: https://docs.payone.com/display/public/PLATFORM/How+to+use+JSON-Responses#HowtouseJSON-Responses-JSON,Clearing-Data
                CustomFieldInstaller::CLEARING_BANK_ACCOUNT => array_merge(array_filter($response['clearing']['BankAccount'] ?? []), [
                    // The PAYONE transaction ID acts as intended purpose of the transfer.
                    // We add this field explicitly here to make clear that the transaction ID is used
                    // as payment reference in context of the prepayment.
                    'Reference' => (string) $response['txid'],
                ]),
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);
    }

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        if (!$this->customerHasCompanyAddress($salesChannelContext)) {
            $definitions['payoneInvoiceBirthday'] = [new NotBlank(), new Birthday(['value' => $this->getMinimumDate()])];
        }

        return $definitions;
    }

    /**
     * Returns the config key for self::getAuthorizationMethod()
     */
    abstract protected function getConfigKey(): string;

    /**
     * Returns the payment method for the request parameters
     */
    abstract protected function getPaymentMethod(): string;
}
