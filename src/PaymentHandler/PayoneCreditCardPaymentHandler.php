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
    final public const REQUEST_PARAM_SAVE_CREDIT_CARD = 'saveCreditCard';
    final public const REQUEST_PARAM_PSEUDO_CARD_PAN = 'pseudoCardPan';
    final public const REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN = 'savedPseudoCardPan';
    final public const REQUEST_PARAM_CARD_EXPIRE_DATE = 'cardExpireDate';
    final public const REQUEST_PARAM_CARD_TYPE = 'cardType';
    final public const REQUEST_PARAM_TRUNCATED_CARD_PAN = 'truncatedCardPan';

    protected TranslatorInterface $translator;

    public function __construct(
        ConfigReaderInterface $configReader,
        protected PayoneClientInterface $client,
        TranslatorInterface $translator,
        private readonly TransactionDataHandlerInterface $dataHandler,
        EntityRepository $lineItemRepository,
        private readonly PaymentStateHandlerInterface $stateHandler,
        private readonly CardRepositoryInterface $cardRepository,
        RequestStack $requestStack,
        private readonly RequestParameterFactory $requestParameterFactory
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);
        $this->translator = $translator;
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
                self::class,
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
        } catch (\Throwable) {
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

        if (strtolower((string) $response['status']) === 'redirect') {
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

        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;

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
