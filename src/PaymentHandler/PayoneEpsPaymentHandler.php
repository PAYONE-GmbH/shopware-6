<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneEpsPaymentHandler extends AbstractPayonePaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /**
     * Valid iDEAL bank groups according to:
     * https://docs.payone.com/pages/releaseview.action?pageId=1213908
     */
    protected const VALID_EPS_BANK_GROUPS = [
        'ARZ_OAB',
        'ARZ_BAF',
        'BA_AUS',
        'ARZ_BCS',
        'EPS_SCHEL',
        'BAWAG_PSK',
        'BAWAG_ESY',
        'SPARDAT_EBS',
        'ARZ_HAA',
        'ARZ_VLH',
        'HRAC_OOS',
        'ARZ_HTB',
        'EPS_OBAG',
        'RAC_RAC',
        'EPS_SCHOELLER',
        'ARZ_OVB',
        'EPS_VRBB',
        'EPS_AAB',
        'EPS_BKS',
        'EPS_BKB',
        'EPS_VLB',
        'EPS_CBGG',
        'EPS_DB',
        'EPS_NOELB',
        'EPS_HBL',
        'EPS_MFB',
        'EPS_SPDBW',
        'EPS_SPDBA',
        'EPS_VKB',
    ];

    private PayoneClientInterface $client;

    private TranslatorInterface $translator;

    private TransactionDataHandlerInterface $dataHandler;

    private PaymentStateHandlerInterface $stateHandler;

    private RequestParameterFactory $requestParameterFactory;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestStack $requestStack,
        RequestParameterFactory $requestParameterFactory
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);

        $this->client = $client;
        $this->translator = $translator;
        $this->dataHandler = $dataHandler;
        $this->stateHandler = $stateHandler;
        $this->requestParameterFactory = $requestParameterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $requestData = $this->fetchRequestData();

        // Get configured authorization method
        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            'epsAuthorizationMethod',
            'authorization'
        );

        $paymentTransaction = PaymentTransaction::fromAsyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        try {
            $this->validate($requestData);
        } catch (PayoneRequestException $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $request = $this->requestParameterFactory->getRequestParameter(
            new PaymentTransactionStruct(
                $paymentTransaction,
                $requestData,
                $salesChannelContext,
                __CLASS__,
                $authorizationMethod
            )
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage']
            );
        } catch (\Throwable $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        if (empty($response['status']) && $response['status'] !== 'REDIRECT') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response);
        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);

        return new RedirectResponse($response['redirecturl']);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $this->stateHandler->handleStateResponse($transaction, (string) $request->query->get('state'));
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_PAID) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    /**
     * @throws PayoneRequestException
     */
    private function validate(RequestDataBag $dataBag): void
    {
        $bankGroup = $dataBag->get('epsBankGroup');

        if (!\in_array($bankGroup, static::VALID_EPS_BANK_GROUPS, true)) {
            throw new PayoneRequestException('No valid EPS bank group');
        }
    }
}
