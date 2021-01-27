<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\CreditCard\CreditCardAuthorizeRequestFactory;
use PayonePayment\Payone\Request\CreditCard\CreditCardPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PayoneCreditCardPaymentHandler extends AbstractPayonePaymentHandler implements AsynchronousPaymentHandlerInterface
{
    public const CARDHOLDER_PATTERN = '^[A-Za-z \-äöüÄÖÜß]{3,50}$';

    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;
    /** @var CreditCardPreAuthorizeRequestFactory */
    private $preAuthRequestFactory;

    /** @var CreditCardAuthorizeRequestFactory */
    private $authRequestFactory;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var PaymentStateHandlerInterface */
    private $stateHandler;

    /** @var CardRepositoryInterface */
    private $cardRepository;

    public function __construct(
        ConfigReaderInterface $configReader,
        CreditCardPreAuthorizeRequestFactory $preAuthRequestFactory,
        CreditCardAuthorizeRequestFactory $authRequestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $lineItemRepository,
        PaymentStateHandlerInterface $stateHandler,
        CardRepositoryInterface $cardRepository,
        RequestStack $requestStack
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);
        $this->preAuthRequestFactory = $preAuthRequestFactory;
        $this->authRequestFactory    = $authRequestFactory;
        $this->client                = $client;
        $this->translator            = $translator;
        $this->dataHandler           = $dataHandler;
        $this->stateHandler          = $stateHandler;
        $this->cardRepository        = $cardRepository;
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

        // Select request factory based on configured authorization method
        $factory = $authorizationMethod === 'preauthorization'
            ? $this->preAuthRequestFactory
            : $this->authRequestFactory;

        $cardholder         = $requestData->get('cardholder');
        $savedPseudoCardPan = $requestData->get('savedPseudoCardPan');

        // Validate cardholder user data
        if (!empty($cardholder)) {
            if (preg_match(sprintf('/%s/i', self::CARDHOLDER_PATTERN), $cardholder) !== 1) {
                throw new AsyncPaymentProcessException(
                    $transaction->getOrderTransaction()->getId(),
                    $this->translator->trans('PayonePayment.errorMessages.genericError')
                );
            }
        }

        // If possible, load saved cardholder from database
        if (empty($cardholder) && !empty($savedPseudoCardPan)) {
            $cardEntity = $this->cardRepository->getExistingCard(
                $salesChannelContext->getCustomer(),
                $savedPseudoCardPan,
                $salesChannelContext->getContext()
            );

            if (!$cardEntity) {
                // The provided pseudo card number was generated from database.
                // We are in trouble if we cannot find the card entity by this number.
                // Probably the request was manipulated, we should catch this here.
                throw new AsyncPaymentProcessException(
                    $transaction->getOrderTransaction()->getId(),
                    $this->translator->trans('PayonePayment.errorMessages.genericError')
                );
            }

            // Set the cardholder for use below
            $cardholder = $cardEntity->getCardholder();
            $requestData->set('cardholder', $cardholder);
        }

        $request = $factory->getRequestParameters(
            $paymentTransaction,
            $requestData,
            $salesChannelContext
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

        $data = $this->prepareTransactionCustomFields($request, $response, $this->getBaseCustomFields($response['status']));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);

        if (null !== $paymentTransaction->getOrder()->getLineItems()) {
            $this->setLineItemCustomFields($paymentTransaction->getOrder()->getLineItems(), $salesChannelContext->getContext());
        }

        $truncatedCardPan = $requestData->get('truncatedCardPan');
        $cardExpireDate   = $requestData->get('cardExpireDate');
        $pseudoCardPan    = $requestData->get('pseudoCardPan');

        if (empty($savedPseudoCardPan)) {
            $expiresAt = DateTime::createFromFormat('ym', $cardExpireDate);

            if (!empty($expiresAt) && null !== $salesChannelContext->getCustomer()) {
                $this->cardRepository->saveCard(
                    $salesChannelContext->getCustomer(),
                    $cardholder,
                    $truncatedCardPan,
                    $pseudoCardPan,
                    $expiresAt,
                    $salesChannelContext->getContext()
                );
            }
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
