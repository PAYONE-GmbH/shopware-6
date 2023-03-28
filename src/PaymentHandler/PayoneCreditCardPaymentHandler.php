<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
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

class PayoneCreditCardPaymentHandler extends AbstractPayonePaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const REQUEST_PARAM_SAVE_CREDIT_CARD = 'saveCreditCard';
    public const REQUEST_PARAM_PSEUDO_CARD_PAN = 'pseudoCardPan';
    public const REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN = 'savedPseudoCardPan';
    public const REQUEST_PARAM_CARD_EXPIRE_DATE = 'cardExpireDate';
    public const REQUEST_PARAM_CARD_TYPE = 'cardType';
    public const REQUEST_PARAM_TRUNCATED_CARD_PAN = 'truncatedCardPan';

    protected PayoneClientInterface $client;

    protected TranslatorInterface $translator;

    private TransactionDataHandlerInterface $dataHandler;

    private PaymentStateHandlerInterface $stateHandler;

    private CardRepositoryInterface $cardRepository;

    private RequestParameterFactory $requestParameterFactory;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepository $lineItemRepository,
        PaymentStateHandlerInterface $stateHandler,
        CardRepositoryInterface $cardRepository,
        RequestStack $requestStack,
        RequestParameterFactory $requestParameterFactory
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);

        $this->client = $client;
        $this->translator = $translator;
        $this->dataHandler = $dataHandler;
        $this->stateHandler = $stateHandler;
        $this->cardRepository = $cardRepository;
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
            'creditCardAuthorizationMethod',
            'preauthorization'
        );

        $paymentTransaction = PaymentTransaction::fromAsyncPaymentTransactionStruct($transaction, $transaction->getOrder());

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

        $customer = $salesChannelContext->getCustomer();
        $savedPseudoCardPan = $requestData->get(self::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN);

        if (empty($savedPseudoCardPan)) {
            $truncatedCardPan = $requestData->get(self::REQUEST_PARAM_TRUNCATED_CARD_PAN);
            $cardExpireDate = $requestData->get(self::REQUEST_PARAM_CARD_EXPIRE_DATE);
            $pseudoCardPan = $requestData->get(self::REQUEST_PARAM_PSEUDO_CARD_PAN);
            $cardType = $requestData->get(self::REQUEST_PARAM_CARD_TYPE);
            $saveCreditCard = $requestData->get(self::REQUEST_PARAM_SAVE_CREDIT_CARD) === 'on';
            $expiresAt = \DateTime::createFromFormat('ym', $cardExpireDate);

            if (!empty($expiresAt) && $customer !== null && $saveCreditCard) {
                $this->cardRepository->saveCard(
                    $customer,
                    $truncatedCardPan,
                    $pseudoCardPan,
                    $cardType,
                    $expiresAt,
                    $salesChannelContext->getContext()
                );
            }
        } else {
            $cardType = '';

            if ($customer) {
                $savedCard = $this->cardRepository->getExistingCard(
                    $customer,
                    $savedPseudoCardPan,
                    $salesChannelContext->getContext()
                );

                $cardType = $savedCard ? $savedCard->getCardType() : '';
            }
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response, [
            'additionalData' => [
                'card_type' => $cardType,
            ],
        ]);
        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);

        if ($paymentTransaction->getOrder()->getLineItems() !== null) {
            $this->setLineItemCustomFields($paymentTransaction->getOrder()->getLineItems(), $salesChannelContext->getContext());
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

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_APPOINTED) {
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
}
