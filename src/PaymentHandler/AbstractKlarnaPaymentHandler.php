<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandler;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
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
use Throwable;

abstract class AbstractKlarnaPaymentHandler extends AbstractPayonePaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var TranslatorInterface */
    protected $translator;
    /** @var CartHasherInterface */
    protected $cartHasher;
    /** @var RequestParameterFactory */
    private $requestParameterFactory;
    /** @var PayoneClientInterface */
    private $client;
    /** @var TransactionDataHandlerInterface */
    private $dataHandler;
    /** @var PaymentStateHandlerInterface */
    private $stateHandler;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        RequestParameterFactory $requestParameterFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        CartHasherInterface $cartHasher,
        PaymentStateHandlerInterface $stateHandler
    ) {
        $this->configReader            = $configReader;
        $this->lineItemRepository      = $lineItemRepository;
        $this->requestStack            = $requestStack;
        $this->translator              = $translator;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->client                  = $client;
        $this->dataHandler             = $dataHandler;
        $this->cartHasher              = $cartHasher;
        parent::__construct($configReader, $lineItemRepository, $requestStack);
        $this->stateHandler = $stateHandler;
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $this->cartHasher->validateRequest($dataBag, $transaction, $salesChannelContext);

        $authToken = $dataBag->get('payoneKlarnaAuthorizationToken');

        if (!$authToken) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $paymentTransaction = PaymentTransaction::fromAsyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            $this->getConfigKeyPrefix() . 'AuthorizationMethod',
            AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE
        );

        $request = $this->requestParameterFactory->getRequestParameter(
            new PaymentTransactionStruct(
                $paymentTransaction,
                $this->filterRequestDataBag($dataBag),
                $salesChannelContext,
                get_class($this),
                $authorizationMethod
            )
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage'],
                $exception
            );
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
                $exception
            );
        }

        if (empty($response['status']) || $response['status'] !== 'OK') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $data = $this->prepareTransactionCustomFields($request, $response, array_merge(
            $this->getBaseCustomFields($response['status']),
            [
                CustomFieldInstaller::CLEARING_TYPE => static::PAYONE_CLEARING_FNC,
            ]
        ));

        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->dataHandler->logResponse($paymentTransaction, $salesChannelContext->getContext(), ['request' => $request, 'response' => $response]);

        return new RedirectResponse($response['redirecturl']);
    }

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

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData, $customFields);
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

    protected function getConfigKeyPrefix(): string
    {
        return ConfigurationPrefixes::CONFIGURATION_PREFIXES[get_class($this)];
    }

    private function filterRequestDataBag(RequestDataBag $dataBag): RequestDataBag
    {
        $dataBag           = clone $dataBag; // prevent modifying the original object
        $allowedParameters = [
            'workorder',
            'payonePaymentMethod',
            'payoneKlarnaAuthorizationToken',
            'carthash',
        ];
        foreach ($dataBag->keys() as $key) {
            if (!in_array($key, $allowedParameters)) {
                $dataBag->remove($key);
            }
        }

        return $dataBag;
    }
}
