<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\CustomerDataPersistor\CustomerDataPersistor;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneDebitPaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    final public const REQUEST_PARAM_SAVE_MANDATE = 'saveMandate';

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $transactionDataHandler,
        OrderActionLogDataHandlerInterface $orderActionLogDataHandler,
        RequestParameterFactory $requestParameterFactory,
        CustomerDataPersistor $customerDataPersistor,
        protected MandateServiceInterface $mandateService
    ) {
        parent::__construct(
            $configReader,
            $lineItemRepository,
            $requestStack,
            $client,
            $translator,
            $transactionDataHandler,
            $orderActionLogDataHandler,
            $requestParameterFactory,
            $customerDataPersistor
        );
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_APPOINTED) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function handleResponse(
        SyncPaymentTransactionStruct $transaction,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext
    ): void {
        parent::handleResponse($transaction, $paymentTransaction, $dataBag, $request, $response, $salesChannelContext);

        $date = \DateTime::createFromFormat('Ymd', $response['mandate']['DateOfSignature']);

        if (empty($date)) {
            throw new \LogicException('could not parse sepa mandate signature date');
        }

        $saveMandate = $dataBag->get(self::REQUEST_PARAM_SAVE_MANDATE) === 'on';

        if ($saveMandate && $salesChannelContext->getCustomer() !== null) {
            $this->mandateService->saveMandate(
                $salesChannelContext->getCustomer(),
                $response['mandate']['Identification'],
                $date,
                $salesChannelContext
            );
        } elseif (!$saveMandate && $salesChannelContext->getCustomer() !== null) {
            $this->mandateService->removeAllMandatesForCustomer(
                $salesChannelContext->getCustomer(),
                $salesChannelContext
            );
        }
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'transactionState' => AbstractPayonePaymentHandler::PAYONE_STATE_PENDING,
            'mandateIdentification' => $response['mandate']['Identification'],
        ];
    }
}
