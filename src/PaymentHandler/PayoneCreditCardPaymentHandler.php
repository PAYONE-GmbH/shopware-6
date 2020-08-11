<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use DateTime;
use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
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

        $truncatedCardPan   = $requestData->get('truncatedCardPan');
        $cardExpireDate     = $requestData->get('cardExpireDate');
        $savedPseudoCardPan = $requestData->get('savedPseudoCardPan');
        $pseudoCardPan      = $requestData->get('pseudoCardPan');

        if (empty($savedPseudoCardPan)) {
            $expiresAt = DateTime::createFromFormat('ym', $cardExpireDate);

            if (!empty($expiresAt) && null !== $salesChannelContext->getCustomer()) {
                $this->cardRepository->saveCard(
                    $salesChannelContext->getCustomer(),
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
        if ($customFields[CustomFieldInstaller::AUTHORIZATION_TYPE] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower($transactionData['txaction']) === TransactionStatusService::ACTION_APPOINTED;
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
